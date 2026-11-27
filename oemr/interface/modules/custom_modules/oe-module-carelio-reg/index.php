<?php

$sessionAllowWrite = true;
require_once(__DIR__ . "/../../../globals.php");

spl_autoload_register(function ($class) {
    $prefix = 'OpenEMR\\Modules\\CarelioCore\\';
    if (strncmp($prefix, $class, strlen($prefix)) === 0) {
        $rel = str_replace('\\', DIRECTORY_SEPARATOR, substr($class, strlen($prefix))) . '.php';
        foreach ([__DIR__ . '/src/' . $rel, dirname(__DIR__) . '/oe-module-carelio-core/src/' . $rel] as $f) {
            if (file_exists($f)) {
                require_once $f;
                return;
            }
        }
    }
});

use OpenEMR\Common\Acl\AclMain;
use OpenEMR\Core\Header;
use OpenEMR\Modules\CarelioCore\Services\RegistrationService;

$isAdmin = AclMain::aclCheckCore('admin', 'super') || AclMain::aclCheckCore('admin', 'practice');
$isFrontDesk = $isAdmin || AclMain::aclCheckCore('patients', 'demo') || AclMain::aclCheckCore('patients', 'appt');
$isMedicalRecords = $isAdmin || AclMain::aclCheckCore('patients', 'med');
$isBilling = $isAdmin || AclMain::aclCheckCore('acct', 'bill') || AclMain::aclCheckCore('acct', 'rep');

if (!$isFrontDesk && !$isMedicalRecords && !$isBilling) {
    echo "<h3>Access Denied: Registration permissions required.</h3>";
    exit;
}

if (isset($_POST['action'])) {
    header('Content-Type: application/json');
    try {
        $action = $_POST['action'];
        $userId = (int)($_SESSION['authUserID'] ?? 1);
        if ($action === 'register_patient') {
            if (!$isFrontDesk) { echo json_encode(['success' => false, 'error' => 'Access Denied: Front Desk role required.']); exit; }
            echo json_encode(RegistrationService::registerPatient($_POST, $userId));
            exit;
        } elseif ($action === 'rapid_register') {
            if (!$isFrontDesk) { echo json_encode(['success' => false, 'error' => 'Access Denied: Front Desk role required.']); exit; }
            echo json_encode(RegistrationService::rapidRegisterUnknownPatient($_POST, $userId));
            exit;
        } elseif ($action === 'reconcile_identity') {
            if (!$isMedicalRecords && !$isFrontDesk) { echo json_encode(['success' => false, 'error' => 'Access Denied.']); exit; }
            echo json_encode(RegistrationService::reconcileIdentity((int)$_POST['temp_id'], (int)($_POST['target_pid'] ?? 0), $userId, $_POST));
            exit;
        } elseif ($action === 'collect_deposit') {
            if (!$isBilling && !$isFrontDesk) { echo json_encode(['success' => false, 'error' => 'Access Denied: Billing role required.']); exit; }
            echo json_encode(RegistrationService::collectDeposit($_POST, $userId));
            exit;
        } elseif ($action === 'detect_duplicates') {
            echo json_encode(['success' => true, 'matches' => RegistrationService::detectDuplicates($_POST)]);
            exit;
        } elseif ($action === 'get_patient_profile') {
            $res = RegistrationService::getPatientProfile((int)$_POST['pid']);
            echo json_encode(['success' => ($res !== null), 'patient' => $res]);
            exit;
        } elseif ($action === 'merge_patients') {
            if (!$isMedicalRecords) { echo json_encode(['success' => false, 'error' => 'Access Denied: Medical Records role required.']); exit; }
            echo json_encode(RegistrationService::mergePatients((int)$_POST['source_pid'], (int)$_POST['target_pid'], $userId, $_POST['reason'] ?? ''));
            exit;
        } elseif ($action === 'unmerge_patients') {
            if (!$isMedicalRecords) { echo json_encode(['success' => false, 'error' => 'Access Denied: Medical Records role required.']); exit; }
            echo json_encode(RegistrationService::unmergePatients((int)$_POST['merge_log_id'], $userId, $_POST['reason'] ?? ''));
            exit;
        } elseif ($action === 'capture_consent') {
            if (!$isFrontDesk) { echo json_encode(['success' => false, 'error' => 'Access Denied: Front Desk role required.']); exit; }
            echo json_encode(RegistrationService::captureConsent($_POST, $userId, $_FILES['consent_scan'] ?? null));
            exit;
        } elseif ($action === 'get_insurance') {
            if (!$isBilling && !$isFrontDesk) { echo json_encode(['success' => false, 'error' => 'Access Denied.']); exit; }
            echo json_encode(['success' => true, 'insurance' => RegistrationService::getInsuranceVerification((int)$_POST['pid'])]);
            exit;
        }
    } catch (\Throwable $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Carelio Hospital EHR - Registration (REG)</title>
    <?php Header::setupHeader(['common']); ?>
    <style>
        body { background-color: #f8fafc; }
        .hms-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        .hms-header { background: linear-gradient(135deg, #0ea5e9, #0284c7); color: #fff; padding: 18px 24px; border-radius: 8px 8px 0 0; }
    </style>
</head>
<body class="p-4">
<div class="container-fluid">
    <div class="hms-card">
        <div class="hms-header d-flex justify-content-between align-items-center">
            <div>
                <h4 class="mb-1"><i class="fa fa-user-plus mr-2"></i>Carelio Hospital EHR — Registration &amp; MPI (Module 2: REG)</h4>
                <p class="mb-0 text-white-50">Rapid Emergency Registration, Duplicate Detection, Merge/Unmerge Audit &amp; Deposits</p>
            </div>
            <span class="badge badge-light px-3 py-2 text-dark font-weight-bold">OpenEMR 8.4.1</span>
        </div>
        <div class="p-4">
            <?php require __DIR__ . '/templates/registration.php'; ?>
        </div>
    </div>
</div>
</body>
</html>
