<?php

$sessionAllowWrite = true;
require_once(__DIR__ . "/../../../globals.php");

spl_autoload_register(function ($class) {
    $prefix = 'OpenEMR\\Modules\\CarelioCore\\';
    if (strncmp($prefix, $class, strlen($prefix)) === 0) {
        $rel = str_replace('\\', DIRECTORY_SEPARATOR, substr($class, strlen($prefix))) . '.php';
        $paths = [
            __DIR__ . '/src/' . $rel,
            dirname(__DIR__) . '/oe-module-carelio-core/src/' . $rel,
        ];
        foreach ($paths as $f) {
            if (file_exists($f)) {
                require_once $f;
                return;
            }
        }
    }
});

use OpenEMR\Common\Acl\AclMain;
use OpenEMR\Core\Header;
use OpenEMR\Modules\CarelioCore\Services\ClinicalDocumentationService;
use OpenEMR\Modules\CarelioCore\Services\ContentApprovalService;

$isAdmin = AclMain::aclCheckCore('admin', 'super') || AclMain::aclCheckCore('admin', 'practice');
$isClinician = $isAdmin || AclMain::aclCheckCore('encounters', 'notes') || AclMain::aclCheckCore('patients', 'med');

if (!$isClinician) {
    echo "<h3>Access Denied: Clinician permissions required.</h3>";
    exit;
}

if (isset($_POST['action'])) {
    header('Content-Type: application/json');
    try {
        $action = $_POST['action'];
        $userId = (int)($_SESSION['authUserID'] ?? 1);
        if ($action === 'cln_seed_lbf') {
            echo json_encode(ClinicalDocumentationService::seedSpecialtyLbfLayouts($userId));
            exit;
        } elseif ($action === 'cln_edit_template') {
            echo json_encode(ContentApprovalService::editItem((int)$_POST['id'], $_POST, $userId));
            exit;
        } elseif ($action === 'resubmit_content') {
            echo json_encode(['success' => ContentApprovalService::submitForApproval((int)$_POST['id'])]);
            exit;
        } elseif ($action === 'approve_content') {
            if (!$isAdmin) { echo json_encode(['success' => false, 'error' => 'Access Denied: Medical Director / Admin role required.']); exit; }
            $ok = ContentApprovalService::approveItem((int)$_POST['id'], $userId);
            echo json_encode(['success' => $ok, 'message' => 'Clinical content approved and certified!']);
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
    <title>Carelio Hospital EHR - Clinical Governance &amp; CLN Reports</title>
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
                <h4 class="mb-1"><i class="fa fa-stethoscope mr-2"></i>Carelio Hospital EHR — Clinical Governance &amp; CLN Reports (Module 4)</h4>
                <p class="mb-0 text-white-50">Specialty LBF Templates, Medical Director Sign-Off &amp; CLN Reports</p>
            </div>
            <span class="badge badge-light px-3 py-2 text-dark font-weight-bold">OpenEMR 8.4.1</span>
        </div>
        <div class="p-4">
            <?php require __DIR__ . '/templates/clinical.php'; ?>
        </div>
    </div>
</div>
</body>
</html>
