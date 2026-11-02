<?php
require_once __DIR__ . '/../../common.php';

use Carelio\DentalEncounter\Encounter;
use Carelio\DentalEncounter\Module;

try {
    // Always use the active patient/encounter, never a posted or URL-supplied patient.
    $dentalPid = Module::patientId();
    $dentalEncounter = Module::encounterId();
    $context = Encounter::context($dentalPid, $dentalEncounter);
    $requestedId = filter_var($_GET['id'] ?? 0, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
    if ($requestedId === false) {
        throw new RuntimeException('Invalid record identifier.', 400);
    }
    $record = Encounter::load($dentalPid, $dentalEncounter, $requestedId);
    if ($requestedId && !$record) {
        throw new RuntimeException('This dental record does not belong to the active encounter or is unavailable.', 404);
    }
    $user = Encounter::row('SELECT fname, mname, lname FROM users WHERE id = ?', [Module::userId()]);
    $provider = trim(implode(' ', array_filter([$user['fname'] ?? '', $user['mname'] ?? '', $user['lname'] ?? ''])));
    $patientName = trim(implode(' ', array_filter([$context['fname'], $context['mname'], $context['lname']])));
    $baseUrl = Module::baseUrl();
    $assetUrl = $baseUrl . '/public/assets';
    $returnUrl = $GLOBALS['webroot'] . '/interface/patient_file/encounter/encounter_top.php';
    $config = [
        'pid' => $dentalPid, 'encounter' => $dentalEncounter,
        'id' => (int)($record['id'] ?? 0), 'revision' => (int)($record['revision'] ?? 0),
        'csrf' => Module::csrfToken(),
        'saveUrl' => $baseUrl . '/public/save.php',
        'viewUrl' => $GLOBALS['webroot'] . '/interface/patient_file/encounter/view_form.php?formname=dental_encounter&id=',
        'returnUrl' => $returnUrl,
        'documentsUrl' => $GLOBALS['webroot'] . '/controller.php?document&list&patient_id=' . $dentalPid,
        'patientName' => $patientName, 'dob' => $context['DOB'],
        'readonly' => !Encounter::canWrite($record) || Encounter::locked($dentalEncounter, $record),
        'defaults' => [
            'visitDate' => substr($context['date'], 0, 10), 'visitTime' => substr($context['date'], 11, 5),
            'encounterId' => (string)$dentalEncounter, 'chartNo' => (string)($context['pubpid'] ?: $dentalPid),
            'provider' => $provider, 'signProvider' => $provider,
        ],
        'data' => $record ? json_decode($record['payload'], true, 64, JSON_THROW_ON_ERROR) : null,
    ];
} catch (Throwable $error) {
    $status = in_array($error->getCode(), [400, 403, 404], true) ? $error->getCode() : 500;
    http_response_code($status);
    echo text($status === 500 ? 'Unable to load the dental record. Check the OpenEMR server log.' : $error->getMessage());
    if ($status === 500) {
        error_log('Dental encounter load failed: ' . get_class($error) . ': ' . $error->getMessage()
            . ' at ' . $error->getFile() . ':' . $error->getLine());
    }
    return;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo text('Dental Encounter — ' . $patientName); ?></title>
    <link rel="icon" href="<?php echo attr($assetUrl); ?>/carelioemr-icon-color.svg">
    <link rel="stylesheet" href="<?php echo attr($assetUrl); ?>/encounter.css?v=1.0.2">
</head>
<body>
<?php require __DIR__ . '/body.php'; ?>
<script>
window.dentalConfig = <?php echo json_encode($config, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR); ?>;
</script>
<script src="<?php echo attr($assetUrl); ?>/encounter.js?v=1.0.2"></script>
<script src="<?php echo attr($assetUrl); ?>/integration.js?v=1.0.2"></script>
</body>
</html>
