<?php
/**
 * Offline smoke test for the parts that do not need OpenEMR: catalog and sanitizer.
 * Run: php tests/smoke.php
 */
$base = dirname(__DIR__);
foreach (['Catalog', 'Sanitizer'] as $c) {
    require_once "$base/src/$c.php";
}
use OpenEMR\Modules\RadiologyRequisition\{Catalog, Sanitizer};

$fail = 0;
function check(string $name, bool $ok): void { global $fail; echo ($ok ? "PASS " : "FAIL ") . $name . "\n"; if (!$ok) $fail++; }

check('8 modalities', count(Catalog::exams()) === 8);
$a = array_keys(Catalog::exams()); $b = array_keys(Catalog::safety()); sort($a); sort($b);
check('every modality has a safety entry', $a === $b);
check('MRI has 7 questions', count(Catalog::safety()['MRI']) === 7);

$in = [
    'patient_name' => '<i>Jo</i> Bloggs', 'mrn' => 'M1', 'dob' => '1980-02-30', 'sex' => 'Banana', 'priority' => 'STAT / Emergency',
    'order_datetime' => '2026-10-24T09:15', 'history' => "a\nb", 'indication' => 'q', 'clinician' => 'Dr X',
    'studies' => [
        ['modality' => 'MRI', 'examination' => 'Knee', 'laterality' => 'Left', 'contrast_protocol' => 'Nonsense',
         'safety' => [0 => 'no', 1 => 'yes', 2 => 'bogus'], 'renal_date' => '2026-10-01', 'creatinine' => '80'],
        ['modality' => 'Laser', 'examination' => 'Chest'],
        ['modality' => 'X-ray', 'examination' => 'Not a real exam'],
        'not an array',
    ],
];
$c = Sanitizer::order($in);
$f = $c['fields'];
check('strips tags', $f['patient_name'] === 'Jo Bloggs');
check('rejects impossible date', $f['dob'] === null);
check('rejects unknown sex', $f['sex'] === '');
check('keeps valid priority', $f['priority'] === 'STAT / Emergency');
check('parses datetime', $f['order_datetime'] === '2026-10-24 09:15:00');
check('drops unknown modality and junk (2 studies kept)', count($c['studies']) === 2);
$mri = $c['studies'][0];
check('invalid contrast falls back to modality default', $mri['contrast_protocol'] === 'Radiologist to determine');
check('safety stored by question text', $mri['safety']['Previous metal injury to the eye?'] === 'yes' || $mri['safety']['Aneurysm clip, metallic implant or foreign body?'] === 'yes');
check('bad answer becomes unknown', $mri['safety']['Pregnant or possibly pregnant? (if applicable)'] === 'unknown');
check('all 7 MRI questions present', count($mri['safety']) === 7);
check('unknown exam blanked', $c['studies'][1]['examination'] === '');

$errs = Sanitizer::finalizeErrors($c, false);
check('finalize flags blank exam', (bool)array_filter($errs, fn($e) => str_contains($e, 'Study 2: choose')));
check('finalize flags unknown answers without ack', (bool)array_filter($errs, fn($e) => str_contains($e, 'Unknown')));
$errs2 = Sanitizer::finalizeErrors($c, true);
check('ack removes the unknown-answer error only', !array_filter($errs2, fn($e) => str_contains($e, 'Unknown')) && (bool)$errs2);
check('no studies blocks finalize', (bool)array_filter(Sanitizer::finalizeErrors(Sanitizer::order(['patient_name' => 'a']), true), fn($e) => str_contains($e, 'at least one')));
check('study cap', count(Sanitizer::order(['studies' => array_fill(0, 40, ['modality' => 'X-ray', 'examination' => 'Chest'])])['studies']) === Catalog::MAX_STUDIES);
check('unknownCount', Sanitizer::unknownCount($c['studies']) === count(array_filter(array_merge(...array_map(fn($s) => array_values($s['safety']), $c['studies'])), fn($a) => $a === 'unknown')));

echo $fail ? "\n$fail FAILED\n" : "\nAll passed\n";
exit($fail ? 1 : 0);
