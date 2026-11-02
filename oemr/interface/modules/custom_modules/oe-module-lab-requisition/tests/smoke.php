<?php
/**
 * Offline smoke test for the parts that do not need OpenEMR: catalog, sanitizer and the sheet renderer.
 * OpenEMR's escaping helpers are stubbed with the same behaviour. Run: php tests/smoke.php
 */
if (!function_exists('text')) {
    function text($s) { return htmlspecialchars((string)$s, ENT_NOQUOTES); }
    function attr($s) { return htmlspecialchars((string)$s, ENT_QUOTES); }
    function xlt($s) { return text($s); }
    function xla($s) { return attr($s); }
    function xl($s) { return (string)$s; }
}
$base = dirname(__DIR__);
foreach (['Catalog', 'Sanitizer', 'Sheet'] as $c) {
    require_once "$base/src/$c.php";
}
use OpenEMR\Modules\LabRequisition\{Catalog, Sanitizer, Sheet};

$fail = 0;
function check(string $name, bool $ok): void { global $fail; echo ($ok ? "PASS " : "FAIL ") . $name . "\n"; if (!$ok) $fail++; }

check('121 orderable tests', count(Catalog::tests()) === 121);
check('7 specimen boxes', count(Catalog::specimens()) === 7);

$in = [
    'patient_name' => '<b>Jane</b> Doe', 'is_stat' => '1', 'clinical_details' => "line1\nline2<script>x</script>",
    'specimen' => ['blood', 'swab', 'bogus'], 'tests' => ['chem_glucose', 'immun_cmv_ab_ig', 'evil_code', ['x']],
    'detail' => ['immun_cmv_ab_ig' => 'IgG', 'chem_glucose' => 'ignored', 'specimen_swab' => 'throat'],
];
$c = Sanitizer::order($in);
check('strips tags', $c['fields']['patient_name'] === 'Jane Doe');
check('keeps newline in clinical details', str_contains($c['fields']['clinical_details'], "\n"));
check('stat on / urgent off', $c['fields']['is_stat'] === 1 && $c['fields']['is_urgent'] === 0);
$codes = array_map(fn($i) => $i['kind'] . ':' . $i['code'], $c['items']);
check('drops unknown codes', $codes === ['specimen:blood', 'specimen:swab', 'test:chem_glucose', 'test:immun_cmv_ab_ig']);
$det = array_column($c['items'], 'detail', 'code');
check('detail only where the form has a line', $det['immun_cmv_ab_ig'] === 'IgG' && $det['chem_glucose'] === '' && $det['swab'] === 'throat');
check('finalize needs a test', Sanitizer::finalizeErrors(Sanitizer::order(['patient_name' => 'A'])) !== []);
check('finalize passes with a test', Sanitizer::finalizeErrors($c) === []);

$order = $c['fields'] + ['specimen' => ['blood'], 'tests' => ['chem_glucose', 'immun_cmv_ab_ig'], 'detail' => ['immun_cmv_ab_ig' => 'IgG']];
$order['patient_name'] = 'A "Q" <O>';
ob_start(); (new Sheet($order, 'edit', 'assets/logo.svg'))->render(); $edit = ob_get_clean();
ob_start(); (new Sheet($order, 'print', 'assets/logo.svg'))->render(); $print = ob_get_clean();
check('edit: 121 test checkboxes', substr_count($edit, 'name="tests[]"') === 121);
check('edit: two ticked tests', substr_count($edit, 'name="tests[]" value="chem_glucose" checked') === 1 && substr_count($edit, 'tests[]') && preg_match_all('/name="tests\[\]"[^>]* checked/', $edit) === 2);
check('edit: values escaped', str_contains($edit, 'value="A &quot;Q&quot; &lt;O&gt;"') && !str_contains($edit, '<O>'));
check('print: boxes disabled, no inputs for text', !str_contains($print, 'type="text"') && str_contains($print, 'disabled'));
check('print: detail shown', str_contains($print, '>IgG<'));
check('print: lab-use box present', str_contains($print, 'FOR LAB USE ONLY'));

echo $fail ? "\n$fail FAILED\n" : "\nAll passed\n";
exit($fail ? 1 : 0);
