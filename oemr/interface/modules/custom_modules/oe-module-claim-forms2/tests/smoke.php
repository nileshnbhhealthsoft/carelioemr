<?php
/**
 * Offline smoke test for the parts that do not need OpenEMR: payload helpers, sanitizer,
 * validator and PDF renderer. Run: php tests/smoke.php /path/to/vendor/autoload.php
 * (any autoloader that provides mPDF + FPDI). Exits non-zero on failure.
 */
$autoload = $argv[1] ?? __DIR__ . '/../../../../../vendor/autoload.php';
require $autoload;
$base = dirname(__DIR__);
foreach (['Payload', 'Sanitizer', 'Validator', 'PdfRenderer'] as $c) {
    require_once "$base/src/$c.php";
}
use OpenEMR\Modules\ClaimForms\{Payload, Sanitizer, Validator, PdfRenderer};

$fail = 0;
function check(string $name, bool $ok): void { global $fail; echo ($ok ? "PASS " : "FAIL ") . $name . "\n"; if (!$ok) $fail++; }

check('toCents 125.50', Payload::toCents('125.50') === 12550);
check('toCents 1,250', Payload::toCents('1,250') === 125000);
check('dollars/cents split', Payload::dollars(197550) === '1,975' && Payload::centsPart(197550) === '50');
check('date DD/MM/YY', Payload::date('2026-09-03', 'DD/MM/YY') === '03/09/26');
check('date blank', Payload::date('0000-00-00', 'DD/MM/YYYY') === '');

$clean = Sanitizer::payload(['policy_no' => '<b>P1</b>', 'bad key!' => 'x', 'icd' => ['a','b','c','d','e'], 'further_services_amount' => '-5',
    'lines' => [['date'=>'2026-01-01','desc'=>'<script>x</script>Visit','dx'=>'12','amount'=>'100']]]);
check('sanitizer strips tags', $clean['policy_no'] === 'P1');
check('sanitizer drops bad keys', !isset($clean['bad key!']));
check('sanitizer caps icd at 4', count($clean['icd']) === 4);
check('sanitizer clamps amount', $clean['further_services_amount'] === 0);
check('sanitizer line dx 1 char', $clean['lines'][0]['dx'] === '1');

$map = json_decode(file_get_contents("$base/forms/nagico_health_claim/map.json"), true);
$payload = ['policy_no'=>'P1','insured_name'=>'A B','patient_name'=>'A B','patient_dob'=>'1980-01-01','icd'=>['J06.9 URI'],
    'lines'=>[['date'=>'2026-09-03','place'=>'Office','desc'=>'Visit','dx'=>'1','amount'=>10000]]];
$none = Validator::validate($map, $payload, ['user_signature'=>null]);
check('validator needs a signature', (bool)array_filter($none, fn($e) => str_contains($e, 'signature')));
$ok = Validator::validate($map, $payload, ['user_signature'=>'/tmp/x.png']);
check('validator passes a complete claim', $ok === []);
$four = $payload; $four['lines'] = array_fill(0, 4, $payload['lines'][0]);
check('validator blocks a 4th line', (bool)array_filter(Validator::validate($map, $four, ['user_signature'=>'/x']), fn($e) => str_contains($e, 'room for 3')));
$badDx = $payload; $badDx['lines'][0]['dx'] = '3';
check('validator checks dx pointer', (bool)array_filter(Validator::validate($map, $badDx, ['user_signature'=>'/x']), fn($e) => str_contains($e, 'diagnosis number')));

$tmp = sys_get_temp_dir() . '/claimforms_smoke';
$r = PdfRenderer::render($map, "$base/forms/nagico_health_claim/background.pdf", $payload, [], '2026-10-03', $tmp);
check('renderer returns a PDF', str_starts_with($r['pdf'], '%PDF'));
file_put_contents("$tmp/out.pdf", $r['pdf']);
$pdftotext = trim((string)(stripos(PHP_OS_FAMILY, 'Windows') === 0 ? shell_exec('where pdftotext 2>NUL') : shell_exec('command -v pdftotext 2>/dev/null')));
if ($pdftotext !== '') {
    $text = shell_exec('pdftotext ' . escapeshellarg("$tmp/out.pdf") . ' - 2>/dev/null') ?? '';
    check('renderer prints the policy number', str_contains($text, 'P1'));
    check('renderer prints total 100 / 00', str_contains($text, '100'));
    check('renderer keeps both pages', substr_count($text, "\f") >= 1);
} else {
    check('renderer writes a non-empty PDF', filesize("$tmp/out.pdf") > 1000);
}

echo $fail ? "\n$fail FAILED\n" : "\nAll passed\n";
exit($fail ? 1 : 0);
