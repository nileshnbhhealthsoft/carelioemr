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
use OpenEMR\Modules\CarelioCore\Services\SchedulingService;

$isAdmin = AclMain::aclCheckCore('admin', 'super') || AclMain::aclCheckCore('admin', 'practice');
$isFrontDesk = $isAdmin || AclMain::aclCheckCore('patients', 'demo') || AclMain::aclCheckCore('patients', 'appt');
$isClinician = $isAdmin || AclMain::aclCheckCore('encounters', 'notes') || AclMain::aclCheckCore('patients', 'med');
$isTherapyStaff = $isClinician || $isFrontDesk;

if (!$isFrontDesk && !$isClinician) {
    echo "<h3>Access Denied: Scheduling permissions required.</h3>";
    exit;
}

if (isset($_POST['action'])) {
    header('Content-Type: application/json');
    try {
        $action = $_POST['action'];
        $userId = (int)($_SESSION['authUserID'] ?? 1);
        if ($action === 'sch_seed_resources') {
            echo json_encode(SchedulingService::seedDefaultResources($userId));
            exit;
        } elseif ($action === 'sch_create_resource') {
            if (!$isAdmin && !$isFrontDesk) { echo json_encode(['success' => false, 'error' => 'Access Denied.']); exit; }
            echo json_encode(SchedulingService::createResource($_POST, $userId));
            exit;
        } elseif ($action === 'sch_toggle_resource') {
            echo json_encode(SchedulingService::toggleResourceAvailability((int)$_POST['resource_id'], (int)$_POST['is_available']));
            exit;
        } elseif ($action === 'sch_create_booking') {
            if (!$isFrontDesk) { echo json_encode(['success' => false, 'error' => 'Access Denied: Front Desk / Scheduling role required.']); exit; }
            echo json_encode(SchedulingService::createBooking($_POST, $userId));
            exit;
        } elseif ($action === 'sch_update_booking_status') {
            echo json_encode(SchedulingService::updateBookingStatus((int)$_POST['booking_id'], $_POST['status'] ?? '', $userId));
            exit;
        } elseif ($action === 'sch_create_series') {
            if (!$isTherapyStaff) { echo json_encode(['success' => false, 'error' => 'Access Denied: Therapy / Dialysis staff required.']); exit; }
            echo json_encode(SchedulingService::createTreatmentSeries($_POST, $userId));
            exit;
        } elseif ($action === 'sch_get_calendar_grid') {
            $mode = $_POST['mode'] ?? 'resource';
            $viewType = $_POST['view_type'] ?? 'week';
            $anchorDate = $_POST['anchor_date'] ?? date('Y-m-d');
            $filterId = !empty($_POST['filter_id']) ? (int)$_POST['filter_id'] : null;
            echo json_encode(['success' => true, 'calendar' => SchedulingService::getCalendarGrid($mode, $viewType, $anchorDate, $filterId)]);
            exit;
        } elseif ($action === 'sch_get_series_sessions') {
            echo json_encode(['success' => true, 'sessions' => SchedulingService::getSeriesSessions((int)$_POST['series_id'])]);
            exit;
        } elseif ($action === 'sch_mark_session_attended') {
            echo json_encode(SchedulingService::markSessionAttended((int)$_POST['booking_id'], $userId));
            exit;
        } elseif ($action === 'sch_update_appt_status') {
            echo json_encode(SchedulingService::updateAppointmentStatus((int)$_POST['pc_eid'], $_POST['status'] ?? '-', $userId));
            exit;
        } elseif ($action === 'sch_create_recall') {
            echo json_encode(SchedulingService::createRecall($_POST, $userId));
            exit;
        } elseif ($action === 'sch_send_reminder') {
            echo json_encode(SchedulingService::sendReminder($_POST, $userId));
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
    <title>Carelio Hospital EHR - Scheduling (SCH)</title>
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
                <h4 class="mb-1"><i class="fa fa-calendar-check-o mr-2"></i>Carelio Hospital EHR — Scheduling &amp; Resource Calendars (Module 3: SCH)</h4>
                <p class="mb-0 text-white-50">Physical Resource Calendars, Treatment Series, Live Flow Board &amp; Recalls</p>
            </div>
            <span class="badge badge-light px-3 py-2 text-dark font-weight-bold">OpenEMR 8.4.1</span>
        </div>
        <div class="p-4">
            <?php require __DIR__ . '/templates/scheduling.php'; ?>
        </div>
    </div>
</div>
</body>
</html>
