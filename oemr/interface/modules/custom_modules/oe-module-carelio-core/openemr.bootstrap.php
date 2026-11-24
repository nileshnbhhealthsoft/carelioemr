<?php

/**
 * Bootstrap for Carelio Hospital EHR (HMS) CORE Foundation Module.
 *
 * @package   OpenEMR
 * @subpackage Carelio Hospital EHR
 * @link      https://www.carelioemr.com
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

use OpenEMR\Core\OEGlobalsBag;
use OpenEMR\Menu\MenuEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Register PSR-4 Autoloader
 */
if (isset($classLoader) && is_object($classLoader) && method_exists($classLoader, 'registerNamespaceIfNotExists')) {
    $classLoader->registerNamespaceIfNotExists('OpenEMR\\Modules\\CarelioCore\\', __DIR__ . DIRECTORY_SEPARATOR . 'src');
} else {
    spl_autoload_register(function ($class) {
        $prefix = 'OpenEMR\\Modules\\CarelioCore\\';
        $base_dir = __DIR__ . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR;
        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) {
            return;
        }
        $relative_class = substr($class, $len);
        $file = $base_dir . str_replace('\\', DIRECTORY_SEPARATOR, $relative_class) . '.php';
        if (file_exists($file)) {
            require_once $file;
        }
    });
}

/**
 * Hook to inject Carelio Hospital EHR into OpenEMR Main Menu
 */
function oe_module_carelio_core_add_menu_item(MenuEvent $event): MenuEvent
{
    $menu = $event->getMenu();

    // Prevent duplicate injection
    foreach ($menu as $item) {
        if (isset($item->menu_id) && $item->menu_id === 'mod_carelio_hms') {
            return $event;
        }
    }

    $basePath = "/interface/modules/custom_modules/oe-module-carelio-core";

    $subLocations = new stdClass();
    $subLocations->requirement = 0;
    $subLocations->target = 'hms_loc';
    $subLocations->menu_id = 'hms_loc';
    $subLocations->label = function_exists('xlt') ? xlt("Ward & Bed Hierarchy") : "Ward & Bed Hierarchy";
    $subLocations->url = $basePath . "/index.php?page=locations";
    $subLocations->children = [];
    $subLocations->acl_req = ["patients", "demo"];

    $subRegistry = new stdClass();
    $subRegistry->requirement = 0;
    $subRegistry->target = 'hms_reg';
    $subRegistry->menu_id = 'hms_reg';
    $subRegistry->label = function_exists('xlt') ? xlt("HMS Module Catalog") : "HMS Module Catalog";
    $subRegistry->url = $basePath . "/index.php?page=modules";
    $subRegistry->children = [];
    $subRegistry->acl_req = ["patients", "demo"];

    $subConfig = new stdClass();
    $subConfig->requirement = 0;
    $subConfig->target = 'hms_cfg';
    $subConfig->menu_id = 'hms_cfg';
    $subConfig->label = function_exists('xlt') ? xlt("Hospital Setup (Saint Lucia)") : "Hospital Setup (Saint Lucia)";
    $subConfig->url = $basePath . "/index.php?page=config";
    $subConfig->children = [];
    $subConfig->acl_req = ["patients", "demo"];

    // Top Level Dropdown Header (no url/target so it operates strictly as a dropdown)
    $topHms = new stdClass();
    $topHms->requirement = 0;
    $topHms->menu_id = 'mod_carelio_hms';
    $topHms->label = function_exists('xlt') ? xlt("Hospital Management (HMS)") : "Hospital Management (HMS)";
    $topHms->children = [$subLocations, $subRegistry, $subConfig];
    $topHms->acl_req = [];

    // Find position before "Admin" or append
    $inserted = false;
    $newMenu = [];
    foreach ($menu as $item) {
        if (isset($item->menu_id) && ($item->menu_id === 'admin' || $item->menu_id === 'admimg')) {
            $newMenu[] = $topHms;
            $inserted = true;
        }
        $newMenu[] = $item;
    }
    if (!$inserted) {
        $newMenu[] = $topHms;
    }

    $event->setMenu($newMenu);
    return $event;
}

/**
 * Register the menu update listener
 */
$dispatcher = null;
if (isset($eventDispatcher) && $eventDispatcher instanceof EventDispatcherInterface) {
    $dispatcher = $eventDispatcher;
} elseif (class_exists(OEGlobalsBag::class) && OEGlobalsBag::getInstance()->hasKernel()) {
    $dispatcher = OEGlobalsBag::getInstance()->getKernel()->getEventDispatcher();
}

if ($dispatcher) {
    $dispatcher->addListener(MenuEvent::MENU_UPDATE, 'oe_module_carelio_core_add_menu_item');
}
