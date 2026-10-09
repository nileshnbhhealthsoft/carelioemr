<?php

require_once __DIR__ . '/_bootstrap.php';

use OpenEMR\Core\Header;
use OpenEMR\Modules\ClaimForms\FormRegistry;

$pid = claimforms_pid();
$claimId = (int)($_GET['id'] ?? 0);
$formId = (string)($_GET['form'] ?? '');
$enc = array_values(array_filter(array_map('intval', (array)($_GET['enc'] ?? []))));

$boot = [
    'csrf' => claimforms_csrf(),
    'api' => 'api.php',
    'claimId' => $claimId ?: null,
    'formId' => $formId,
    'enc' => $enc,
    'i18n' => [
        'save' => xla('Save draft'), 'generate' => xla('Generate PDF'), 'saved' => xla('Saved'),
        'generating' => xla('Generating...'), 'open' => xla('Open PDF'), 'add' => xla('Add line'),
        'remove' => xla('Remove'), 'search' => xla('Search ICD-10 code or text'), 'none' => xla('None'),
        'tooMany' => xla('The form has room for 3 lines. Split this claim in two.'),
        'noMatch' => xla('No matching codes found (is an ICD-10 code set installed?)'),
        'useTyped' => xla('Use as typed:'), 'searchFailed' => xla('Search failed. See the server log.'),
        'noCpt' => xla('No CPT codes found. Type the operation instead.'),
        'cptHint' => xla('Pick a CPT code or type the operation'),
        'noSignature' => xla('You have no signature saved. Upload one before generating.'),
    ],
];
if ($pid <= 0 || ($claimId <= 0 && !FormRegistry::exists($formId))) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title><?php echo xlt('Insurance Claim'); ?></title>
    <?php Header::setupHeader(['common']); ?>
    <link rel="stylesheet" href="assets/claim.css?v=<?php echo attr((string)filemtime(__DIR__ . '/assets/claim.css')); ?>">
</head>
<body class="body_top">
<div class="container-fluid mt-3" id="claim-app">
    <p class="text-muted"><?php echo xlt('Loading...'); ?></p>
</div>
<script>window.CLAIMFORMS = <?php echo json_encode($boot, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;</script>
<script src="assets/claim.js?v=<?php echo attr((string)filemtime(__DIR__ . '/assets/claim.js')); ?>"></script>
</body>
</html>
