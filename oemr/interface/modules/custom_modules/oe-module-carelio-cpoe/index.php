<?php

$sessionAllowWrite = true;
require_once(__DIR__ . "/../../../globals.php");

spl_autoload_register(function ($class) {
    $cpoePrefix = 'OpenEMR\\Modules\\CarelioCpoe\\';
    if (strncmp($cpoePrefix, $class, strlen($cpoePrefix)) === 0) {
        $rel = str_replace('\\', DIRECTORY_SEPARATOR, substr($class, strlen($cpoePrefix))) . '.php';
        $local = __DIR__ . '/src/' . $rel;
        if (file_exists($local)) {
            require_once $local;
            return;
        }
    }

    $corePrefix = 'OpenEMR\\Modules\\CarelioCore\\';
    if (strncmp($corePrefix, $class, strlen($corePrefix)) === 0) {
        $rel = str_replace('\\', DIRECTORY_SEPARATOR, substr($class, strlen($corePrefix))) . '.php';
        $coreFile = dirname(__DIR__) . '/oe-module-carelio-core/src/' . $rel;
        if (file_exists($coreFile)) {
            require_once $coreFile;
        }
    }
});

use OpenEMR\Common\Acl\AclMain;
use OpenEMR\Core\Header;
use OpenEMR\Modules\CarelioCpoe\Services\CpoeService;

$isAdmin = AclMain::aclCheckCore('admin', 'super') || AclMain::aclCheckCore('admin', 'practice');
$isClinician = $isAdmin || AclMain::aclCheckCore('encounters', 'notes') || AclMain::aclCheckCore('patients', 'med');
$isAttending = $isAdmin || AclMain::aclCheckCore('encounters', 'auth_a');

if (!$isClinician) {
    echo "<h3>Access Denied: Clinician permissions required for CPOE.</h3>";
    exit;
}

if (isset($_POST['action'])) {
    header('Content-Type: application/json');
    try {
        $action = $_POST['action'];
        $userId = (int)($_SESSION['authUserID'] ?? 1);
        if ($action === 'cpoe_apply_order_set') {
            $isResident = !empty($_POST['is_resident']);
            echo json_encode(CpoeService::applyOrderSet((int)$_POST['order_set_id'], (int)$_POST['pid'], (int)$_POST['encounter'], $userId, $isResident));
            exit;
        } elseif ($action === 'cpoe_resident_order') {
            echo json_encode(CpoeService::placeResidentHighRiskOrder($_POST, $userId));
            exit;
        } elseif ($action === 'cpoe_cosign') {
            if (!$isAttending) {
                echo json_encode(['success' => false, 'error' => 'Access Denied: Attending Physician / Admin role required to co-sign orders.']);
                exit;
            }
            echo json_encode(CpoeService::cosignOrder((int)$_POST['cosign_id'], $userId, $_POST['decision'] ?? 'cosigned', $_POST['notes'] ?? ''));
            exit;
        } elseif ($action === 'cpoe_record_result') {
            echo json_encode(CpoeService::recordAndEvaluateResult($_POST, $userId));
            exit;
        } elseif ($action === 'cpoe_ack_alert') {
            echo json_encode(CpoeService::acknowledgeAlert((int)$_POST['alert_id'], $userId, $_POST['notes'] ?? ''));
            exit;
        } elseif ($action === 'cpoe_escalate_alert') {
            $aid = !empty($_POST['alert_id']) ? (int)$_POST['alert_id'] : null;
            echo json_encode(CpoeService::escalateAlert($aid, $userId));
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
    <title>Carelio Hospital EHR - Orders &amp; Results (CPOE)</title>
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
                <h4 class="mb-1"><i class="fa fa-list-ol mr-2"></i>Carelio Hospital EHR — Orders &amp; Results (Module 5: CPOE)</h4>
                <p class="mb-0 text-white-50">1-Click Order Sets, Resident Co-Signature Queue, Critical Panic Alerts &amp; Escalation</p>
            </div>
            <span class="badge badge-light px-3 py-2 text-dark font-weight-bold">OpenEMR 8.4.1</span>
        </div>
        <div class="p-4">
            <?php require __DIR__ . '/templates/cpoe.php'; ?>
        </div>
    </div>
</div>
</body>
</html>
