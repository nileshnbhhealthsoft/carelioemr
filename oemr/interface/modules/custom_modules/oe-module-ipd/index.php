<?php

/**
 * Inpatient (IPD) & Hospital Management Module
 * Main Dashboard and Application Shell
 *
 * @package   OpenEMR
 * @subpackage CarelioEMR IPD Module
 * @link      https://www.open-emr.org
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

$sessionAllowWrite = true;
require_once(__DIR__ . "/../../../globals.php");

use OpenEMR\Core\Header;
use OpenEMR\Core\OEGlobalsBag;
use OpenEMR\Modules\Ipd\BootstrapService;
use OpenEMR\Modules\Ipd\Controller\IpdDispatch;
use OpenEMR\Modules\Ipd\Controller\AdmissionController;
use OpenEMR\Modules\Ipd\Controller\BedController;
use OpenEMR\Modules\Ipd\Controller\ClinicalController;
use OpenEMR\Modules\Ipd\Controller\NursingController;
use OpenEMR\Modules\Ipd\Controller\OtController;
use OpenEMR\Modules\Ipd\Controller\BillingController;
use OpenEMR\Modules\Ipd\Controller\DischargeController;
use OpenEMR\Modules\Ipd\Controller\EmergencyController;
use OpenEMR\Modules\Ipd\Controller\AttendantController;
use OpenEMR\Modules\Ipd\Controller\ReportController;

// 1. Check Module ACL
if (!BootstrapService::verifyAcl('patients', 'med')) {
    die("<h3>" . (function_exists('xlt') ? xlt("Not Authorized!") : "Not Authorized!") . "</h3>");
}

// 2. Route AJAX & API requests
if (isset($_REQUEST['action'])) {
    $dispatcher = new IpdDispatch();
    $dispatcher->dispatch((string)$_REQUEST['action'], $_REQUEST);
    exit;
}

// 3. Handle Printable Discharge Summary view
if (isset($_GET['view_summary'])) {
    $dischargeCtrl = new DischargeController();
    $summaryData = $dischargeCtrl->getDischargeSummary((int)$_GET['view_summary']);
    include __DIR__ . "/templates/print_discharge_summary.php";
    exit;
}

// 4. Fetch Data for Server-side Initial Render
$admCtrl = new AdmissionController();
$bedCtrl = new BedController();
$clinicalCtrl = new ClinicalController();
$nursingCtrl = new NursingController();
$otCtrl = new OtController();
$billingCtrl = new BillingController();
$dischargeCtrl = new DischargeController();
$emergencyCtrl = new EmergencyController();
$attendantCtrl = new AttendantController();
$reportCtrl = new ReportController();

$bedMatrixData = $bedCtrl->getBedMatrix();
$admissionsList = $admCtrl->listAdmissions();
$dischargedList = $admCtrl->listAdmissions('discharged');
$bedsList = $bedMatrixData['all_beds'] ?? [];
$wardsList = $bedMatrixData['wards'] ?? [];

$recentRounds = \OpenEMR\Modules\Ipd\Database::fetchAll(
    "SELECT r.*, a.ipd_number, b.bed_number, p.fname, p.lname, u.fname AS doc_fname, u.lname AS doc_lname 
     FROM ipd_doctor_rounds r
     JOIN ipd_admissions a ON r.admission_id = a.id
     JOIN patient_data p ON a.pid = p.pid
     LEFT JOIN ipd_beds b ON a.bed_id = b.id
     LEFT JOIN users u ON r.doctor_id = u.id
     ORDER BY r.round_date DESC LIMIT 20"
);

$activeMeds = \OpenEMR\Modules\Ipd\Database::fetchAll(
    "SELECT m.*, a.ipd_number, b.bed_number, p.fname, p.lname, u.fname AS doc_fname, u.lname AS doc_lname 
     FROM ipd_medication_orders m
     JOIN ipd_admissions a ON m.admission_id = a.id
     JOIN patient_data p ON a.pid = p.pid
     LEFT JOIN ipd_beds b ON a.bed_id = b.id
     LEFT JOIN users u ON m.doctor_id = u.id
     WHERE m.status = 'active'
     ORDER BY m.id DESC LIMIT 25"
);

$recentVitals = \OpenEMR\Modules\Ipd\Database::fetchAll(
    "SELECT v.*, a.ipd_number, b.bed_number, p.fname, p.lname, u.fname AS nurse_fname, u.lname AS nurse_lname 
     FROM ipd_vitals v
     JOIN ipd_admissions a ON v.admission_id = a.id
     JOIN patient_data p ON a.pid = p.pid
     LEFT JOIN ipd_beds b ON a.bed_id = b.id
     LEFT JOIN users u ON v.recorded_by = u.id
     ORDER BY v.recorded_at DESC LIMIT 25"
);

$recentMar = \OpenEMR\Modules\Ipd\Database::fetchAll(
    "SELECT mar.*, m.medicine_name, m.dosage, m.route, a.ipd_number, b.bed_number, p.fname, p.lname, u.fname AS nurse_fname, u.lname AS nurse_lname 
     FROM ipd_mar_administrations mar
     JOIN ipd_medication_orders m ON mar.order_id = m.id
     JOIN ipd_admissions a ON mar.admission_id = a.id
     JOIN patient_data p ON a.pid = p.pid
     LEFT JOIN ipd_beds b ON a.bed_id = b.id
     LEFT JOIN users u ON mar.administered_by = u.id
     ORDER BY mar.administered_time DESC LIMIT 25"
);

$theatresList = $otCtrl->listTheatres();
$surgeriesList = $otCtrl->listSurgeries();

$billsList = \OpenEMR\Modules\Ipd\Database::fetchAll(
    "SELECT b.*, a.ipd_number, p.fname, p.lname 
     FROM ipd_bills b
     JOIN ipd_admissions a ON b.admission_id = a.id
     JOIN patient_data p ON a.pid = p.pid
     ORDER BY b.id DESC LIMIT 30"
);

$tariffsList = $billingCtrl->listTariffs();
$packagesList = $billingCtrl->listPackages();
$triageList = $emergencyCtrl->listTriage();
$passesList = $attendantCtrl->listPasses();
$kpis = $reportCtrl->getHospitalKpis();

$doctorsList = BootstrapService::getActiveDoctors();
$staffList = BootstrapService::getActiveStaff();

$assetPath = OEGlobalsBag::getInstance()->getWebRoot() . "/interface/modules/custom_modules/oe-module-ipd/public";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo function_exists('xlt') ? xlt("Inpatient (IPD) & Hospital Management") : "Inpatient (IPD) & Hospital Management"; ?></title>
    
    <?php Header::setupHeader(['opener', 'datetime-picker', 'jspdf', 'jstiff']); ?>
    
    <link rel="stylesheet" href="<?php echo htmlspecialchars($assetPath); ?>/css/ipd.css?v=1.0.0">
</head>
<body class="ipd-app">

    <!-- Top Header Banner -->
    <header class="ipd-header">
        <div class="ipd-title-group">
            <h1>
                <i class="fa fa-hospital-alt"></i>
                Carelio Inpatient (IPD) & Hospital Management
            </h1>
            <p>Integrated Hospital Operations: Admissions, Live Bed Board, Clinical Rounds, Nursing Care, MAR, OT, Billing & Clearances</p>
        </div>
    </header>

    <!-- KPI Summary Row -->
    <div class="ipd-kpi-row">
        <div class="ipd-kpi-card">
            <div class="ipd-kpi-icon primary"><i class="fa fa-procedures"></i></div>
            <div class="ipd-kpi-data">
                <h3><?php echo (int)($kpis['beds']['occupied'] ?? 0); ?> / <?php echo (int)($kpis['beds']['total'] ?? 0); ?></h3>
                <span>Beds Occupied (<?php echo number_format((float)($kpis['beds']['occupancy_rate'] ?? 0), 0); ?>%)</span>
            </div>
        </div>
        <div class="ipd-kpi-card">
            <div class="ipd-kpi-icon success"><i class="fa fa-bed"></i></div>
            <div class="ipd-kpi-data">
                <h3><?php echo (int)($kpis['beds']['available'] ?? 0); ?></h3>
                <span>Available Beds</span>
            </div>
        </div>
        <div class="ipd-kpi-card">
            <div class="ipd-kpi-icon purple"><i class="fa fa-user-check"></i></div>
            <div class="ipd-kpi-data">
                <h3><?php echo (int)($kpis['patients']['current_inpatients'] ?? 0); ?></h3>
                <span>Active Inpatients</span>
            </div>
        </div>
        <div class="ipd-kpi-card">
            <div class="ipd-kpi-icon danger"><i class="fa fa-ambulance"></i></div>
            <div class="ipd-kpi-data">
                <h3><?php echo (int)($kpis['patients']['emergency_today'] ?? 0); ?></h3>
                <span>Emergency Triage</span>
            </div>
        </div>
        <div class="ipd-kpi-card">
            <div class="ipd-kpi-icon warning"><i class="fa fa-cut"></i></div>
            <div class="ipd-kpi-data">
                <h3><?php echo count($surgeriesList); ?></h3>
                <span>Surgeries (OT)</span>
            </div>
        </div>
        <div class="ipd-kpi-card">
            <div class="ipd-kpi-icon info"><i class="fa fa-file-invoice-dollar"></i></div>
            <div class="ipd-kpi-data">
                <h3>$<?php echo number_format((float)($kpis['financials']['total_collected'] ?? 0), 0); ?></h3>
                <span>IPD Collections</span>
            </div>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <nav class="ipd-tabs-nav">
        <button type="button" class="ipd-tab-btn active" data-tab="dashboard">
            <i class="fa fa-th-large"></i> Bed Board
        </button>
        <button type="button" class="ipd-tab-btn" data-tab="admissions">
            <i class="fa fa-procedures"></i> Admissions (IPD)
        </button>
        <button type="button" class="ipd-tab-btn" data-tab="wards_beds">
            <i class="fa fa-cogs"></i> Bed & Ward Setup
        </button>
        <button type="button" class="ipd-tab-btn" data-tab="clinical">
            <i class="fa fa-user-md"></i> Doctor Rounds & Orders
        </button>
        <button type="button" class="ipd-tab-btn" data-tab="nursing">
            <i class="fa fa-user-nurse"></i> Nursing & MAR
        </button>
        <button type="button" class="ipd-tab-btn" data-tab="ot">
            <i class="fa fa-cut"></i> Operation Theatre
        </button>
        <button type="button" class="ipd-tab-btn" data-tab="billing">
            <i class="fa fa-file-invoice-dollar"></i> Billing & Packages
        </button>
        <button type="button" class="ipd-tab-btn" data-tab="emergency">
            <i class="fa fa-ambulance"></i> Emergency / Triage
        </button>
        <button type="button" class="ipd-tab-btn" data-tab="discharge">
            <i class="fa fa-sign-out-alt"></i> Discharge Management
        </button>
        <button type="button" class="ipd-tab-btn" data-tab="attendants">
            <i class="fa fa-id-badge"></i> Attendant Passes
        </button>
        <button type="button" class="ipd-tab-btn" data-tab="reports">
            <i class="fa fa-chart-bar"></i> Reports & Analytics
        </button>
    </nav>

    <!-- Main Views Container -->
    <main class="ipd-main-content">
        <!-- Tab 1: Live Bed Matrix & Dashboard -->
        <div id="panel-dashboard" class="ipd-view-panel active">
            <?php include __DIR__ . "/templates/dashboard_bed_matrix.php"; ?>
        </div>

        <!-- Tab 2: Admissions & Patient Flow -->
        <div id="panel-admissions" class="ipd-view-panel">
            <?php include __DIR__ . "/templates/admissions_view.php"; ?>
        </div>

        <!-- Tab 3: Bed & Ward Infrastructure Setup -->
        <div id="panel-wards_beds" class="ipd-view-panel">
            <?php include __DIR__ . "/templates/ward_bed_mgmt.php"; ?>
        </div>

        <!-- Tab 4: Doctor Rounds & Clinical Notes -->
        <div id="panel-clinical" class="ipd-view-panel">
            <?php include __DIR__ . "/templates/clinical_rounds.php"; ?>
        </div>

        <!-- Tab 5: Nursing, Vitals & MAR -->
        <div id="panel-nursing" class="ipd-view-panel">
            <?php include __DIR__ . "/templates/nursing_vitals_mar.php"; ?>
        </div>

        <!-- Tab 6: Operation Theatre -->
        <div id="panel-ot" class="ipd-view-panel">
            <?php include __DIR__ . "/templates/operation_theatre.php"; ?>
        </div>

        <!-- Tab 7: Billing & Hospital Packages -->
        <div id="panel-billing" class="ipd-view-panel">
            <?php include __DIR__ . "/templates/billing_packages.php"; ?>
        </div>

        <!-- Tab 8: Emergency & Triage -->
        <div id="panel-emergency" class="ipd-view-panel">
            <?php include __DIR__ . "/templates/emergency_triage.php"; ?>
        </div>

        <!-- Tab 9: Discharge Clearances -->
        <div id="panel-discharge" class="ipd-view-panel">
            <?php include __DIR__ . "/templates/discharge_mgmt.php"; ?>
        </div>

        <!-- Tab 10: Attendant Passes -->
        <div id="panel-attendants" class="ipd-view-panel">
            <?php include __DIR__ . "/templates/attendant_services.php"; ?>
        </div>

        <!-- Tab 11: Reports & Analytics -->
        <div id="panel-reports" class="ipd-view-panel">
            <?php include __DIR__ . "/templates/reports_analytics.php"; ?>
        </div>
    </main>

    <!-- Modals Container -->
    <?php include __DIR__ . "/templates/modals.php"; ?>

    <!-- Client JavaScript -->
    <script src="<?php echo htmlspecialchars($assetPath); ?>/js/ipd.js?v=1.0.0"></script>
</body>
</html>
