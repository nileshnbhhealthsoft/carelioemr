<?php

/**
 * Bootstrap for Custom Inpatient (IPD) and Hospital Management Module
 *
 * @package   OpenEMR
 * @subpackage CarelioEMR IPD Module
 * @link      https://www.open-emr.org
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

use OpenEMR\Core\OEGlobalsBag;
use OpenEMR\Menu\MenuEvent;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @var \OpenEMR\Core\ModulesClassLoader $classLoader
 */
$classLoader->registerNamespaceIfNotExists('OpenEMR\\Modules\\Ipd\\', __DIR__ . DIRECTORY_SEPARATOR . 'src');

/**
 * @global EventDispatcherInterface $dispatcher Injected by OpenEMR module loader
 */
$eventDispatcher = OEGlobalsBag::getInstance()->getKernel()->getEventDispatcher();

/**
 * Hook to inject Inpatient (IPD) into OpenEMR Main Menu
 */
function oe_module_ipd_add_menu_item(MenuEvent $event): MenuEvent
{
    $menu = $event->getMenu();

    // Prevent duplicate injection
    foreach ($menu as $item) {
        if (isset($item->menu_id) && $item->menu_id === 'mod_ipd_top') {
            return $event;
        }
    }

    $basePath = "/interface/modules/custom_modules/oe-module-ipd";

    // Submenu Items
    $subDashboard = new stdClass();
    $subDashboard->requirement = 0;
    $subDashboard->target = 'ipd_dash';
    $subDashboard->menu_id = 'ipd_dash';
    $subDashboard->label = function_exists('xlt') ? xlt("Bed Matrix & Overview") : "Bed Matrix & Overview";
    $subDashboard->url = $basePath . "/index.php?tab=dashboard";
    $subDashboard->children = [];
    $subDashboard->acl_req = ["patients", "med"];

    $subAdmissions = new stdClass();
    $subAdmissions->requirement = 0;
    $subAdmissions->target = 'ipd_admit';
    $subAdmissions->menu_id = 'ipd_admit';
    $subAdmissions->label = function_exists('xlt') ? xlt("IPD Admissions & Transfers") : "IPD Admissions & Transfers";
    $subAdmissions->url = $basePath . "/index.php?tab=admissions";
    $subAdmissions->children = [];
    $subAdmissions->acl_req = ["patients", "demo"];

    $subWardsBeds = new stdClass();
    $subWardsBeds->requirement = 0;
    $subWardsBeds->target = 'ipd_beds';
    $subWardsBeds->menu_id = 'ipd_beds';
    $subWardsBeds->label = function_exists('xlt') ? xlt("Bed & Ward Management") : "Bed & Ward Management";
    $subWardsBeds->url = $basePath . "/index.php?tab=wards_beds";
    $subWardsBeds->children = [];
    $subWardsBeds->acl_req = ["patients", "med"];

    $subClinical = new stdClass();
    $subClinical->requirement = 0;
    $subClinical->target = 'ipd_clinical';
    $subClinical->menu_id = 'ipd_clinical';
    $subClinical->label = function_exists('xlt') ? xlt("Doctor Rounds & Clinical") : "Doctor Rounds & Clinical";
    $subClinical->url = $basePath . "/index.php?tab=clinical";
    $subClinical->children = [];
    $subClinical->acl_req = ["patients", "med"];

    $subNursing = new stdClass();
    $subNursing->requirement = 0;
    $subNursing->target = 'ipd_nursing';
    $subNursing->menu_id = 'ipd_nursing';
    $subNursing->label = function_exists('xlt') ? xlt("Nursing Care & MAR") : "Nursing Care & MAR";
    $subNursing->url = $basePath . "/index.php?tab=nursing";
    $subNursing->children = [];
    $subNursing->acl_req = ["patients", "med"];

    $subOT = new stdClass();
    $subOT->requirement = 0;
    $subOT->target = 'ipd_ot';
    $subOT->menu_id = 'ipd_ot';
    $subOT->label = function_exists('xlt') ? xlt("Operation Theatre (OT)") : "Operation Theatre (OT)";
    $subOT->url = $basePath . "/index.php?tab=ot";
    $subOT->children = [];
    $subOT->acl_req = ["patients", "med"];

    $subBilling = new stdClass();
    $subBilling->requirement = 0;
    $subBilling->target = 'ipd_billing';
    $subBilling->menu_id = 'ipd_billing';
    $subBilling->label = function_exists('xlt') ? xlt("Inpatient Billing & Packages") : "Inpatient Billing & Packages";
    $subBilling->url = $basePath . "/index.php?tab=billing";
    $subBilling->children = [];
    $subBilling->acl_req = ["acct", "bill"];

    $subEmergency = new stdClass();
    $subEmergency->requirement = 0;
    $subEmergency->target = 'ipd_emergency';
    $subEmergency->menu_id = 'ipd_emergency';
    $subEmergency->label = function_exists('xlt') ? xlt("Emergency & Casualty Triage") : "Emergency & Casualty Triage";
    $subEmergency->url = $basePath . "/index.php?tab=emergency";
    $subEmergency->children = [];
    $subEmergency->acl_req = ["patients", "med"];

    $subDischarge = new stdClass();
    $subDischarge->requirement = 0;
    $subDischarge->target = 'ipd_discharge';
    $subDischarge->menu_id = 'ipd_discharge';
    $subDischarge->label = function_exists('xlt') ? xlt("Discharge Management") : "Discharge Management";
    $subDischarge->url = $basePath . "/index.php?tab=discharge";
    $subDischarge->children = [];
    $subDischarge->acl_req = ["patients", "med"];

    $subAttendants = new stdClass();
    $subAttendants->requirement = 0;
    $subAttendants->target = 'ipd_attendants';
    $subAttendants->menu_id = 'ipd_attendants';
    $subAttendants->label = function_exists('xlt') ? xlt("Attendant Passes & Services") : "Attendant Passes & Services";
    $subAttendants->url = $basePath . "/index.php?tab=attendants";
    $subAttendants->children = [];
    $subAttendants->acl_req = ["patients", "demo"];

    $subReports = new stdClass();
    $subReports->requirement = 0;
    $subReports->target = 'ipd_reports';
    $subReports->menu_id = 'ipd_reports';
    $subReports->label = function_exists('xlt') ? xlt("IPD Reports & Analytics") : "IPD Reports & Analytics";
    $subReports->url = $basePath . "/index.php?tab=reports";
    $subReports->children = [];
    $subReports->acl_req = ["acct", "rep"];

    $subConfig = new stdClass();
    $subConfig->requirement = 0;
    $subConfig->target = 'ipd_config';
    $subConfig->menu_id = 'ipd_config';
    $subConfig->label = function_exists('xlt') ? xlt("IPD Settings & Tariffs") : "IPD Settings & Tariffs";
    $subConfig->url = $basePath . "/moduleConfig.php";
    $subConfig->children = [];
    $subConfig->acl_req = ["admin", "practice"];

    // Top Level Inpatient (IPD) Menu
    $topMenu = new stdClass();
    $topMenu->requirement = 0;
    $topMenu->target = 'ipd_top';
    $topMenu->menu_id = 'mod_ipd_top';
    $topMenu->label = function_exists('xlt') ? xlt("Inpatient (IPD)") : "Inpatient (IPD)";
    $topMenu->url = $basePath . "/index.php";
    $topMenu->children = [
        $subDashboard,
        $subAdmissions,
        $subWardsBeds,
        $subClinical,
        $subNursing,
        $subOT,
        $subBilling,
        $subEmergency,
        $subDischarge,
        $subAttendants,
        $subReports,
        $subConfig,
    ];
    $topMenu->acl_req = [];

    // Insert into menu before or after Patients/Fees
    $inserted = false;
    $newMenu = [];
    foreach ($menu as $item) {
        $newMenu[] = $item;
        if (isset($item->menu_id) && ($item->menu_id === 'pat0' || $item->menu_id === 'modimg')) {
            $newMenu[] = $topMenu;
            $inserted = true;
        }
    }
    if (!$inserted) {
        $newMenu[] = $topMenu;
    }

    $event->setMenu($newMenu);
    return $event;
}

$eventDispatcher->addListener(MenuEvent::MENU_UPDATE, 'oe_module_ipd_add_menu_item');
