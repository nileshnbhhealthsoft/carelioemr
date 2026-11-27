<?php

use OpenEMR\Core\OEGlobalsBag;
use OpenEMR\Menu\MenuEvent;
use OpenEMR\Modules\CarelioCpoe\Event\CpoeEventSubscriber;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

spl_autoload_register(function ($class) {
    $cpoePrefix = 'OpenEMR\\Modules\\CarelioCpoe\\';
    if (strncmp($cpoePrefix, $class, strlen($cpoePrefix)) === 0) {
        $rel = str_replace('\\', DIRECTORY_SEPARATOR, substr($class, strlen($cpoePrefix))) . '.php';
        $local = __DIR__ . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . $rel;
        if (file_exists($local)) {
            require_once $local;
            return;
        }
    }

    $corePrefix = 'OpenEMR\\Modules\\CarelioCore\\';
    if (strncmp($corePrefix, $class, strlen($corePrefix)) === 0) {
        $rel = str_replace('\\', DIRECTORY_SEPARATOR, substr($class, strlen($corePrefix))) . '.php';
        $coreFile = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'oe-module-carelio-core' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . $rel;
        if (file_exists($coreFile)) {
            require_once $coreFile;
        }
    }
});

function oe_module_carelio_cpoe_add_menu_item(MenuEvent $event): MenuEvent
{
    $menu = $event->getMenu();
    $item = new stdClass();
    $item->requirement = 0;
    $item->target = 'hms_cpoe_mod';
    $item->menu_id = 'hms_cpoe_mod';
    $item->label = function_exists('xlt') ? xlt("Orders & Results (CPOE)") : "Orders & Results (CPOE)";
    $item->url = "/interface/modules/custom_modules/oe-module-carelio-cpoe/index.php";
    $item->children = [];
    $item->acl_req = ["encounters", "notes"];

    foreach ($menu as $top) {
        if (isset($top->menu_id) && $top->menu_id === 'mod_carelio_hms') {
            foreach ($top->children as $ch) {
                if (($ch->menu_id ?? '') === 'hms_cpoe_mod') {
                    return $event;
                }
            }
            $top->children[] = $item;
            $event->setMenu($menu);
            return $event;
        }
    }

    $topHms = new stdClass();
    $topHms->requirement = 0;
    $topHms->menu_id = 'mod_carelio_hms';
    $topHms->label = function_exists('xlt') ? xlt("Hospital Management (HMS)") : "Hospital Management (HMS)";
    $topHms->children = [$item];
    $topHms->acl_req = [];

    $inserted = false;
    $newMenu = [];
    foreach ($menu as $m) {
        if (isset($m->menu_id) && ($m->menu_id === 'admin' || $m->menu_id === 'admimg')) {
            $newMenu[] = $topHms;
            $inserted = true;
        }
        $newMenu[] = $m;
    }
    if (!$inserted) {
        $newMenu[] = $topHms;
    }
    $event->setMenu($newMenu);
    return $event;
}

$dispatcher = null;
if (isset($eventDispatcher) && $eventDispatcher instanceof EventDispatcherInterface) {
    $dispatcher = $eventDispatcher;
} elseif (class_exists(OEGlobalsBag::class) && OEGlobalsBag::getInstance()->hasKernel()) {
    $dispatcher = OEGlobalsBag::getInstance()->getKernel()->getEventDispatcher();
}
if ($dispatcher) {
    $dispatcher->addListener(MenuEvent::MENU_UPDATE, 'oe_module_carelio_cpoe_add_menu_item', 10);
    if (class_exists(CpoeEventSubscriber::class)) {
        $dispatcher->addSubscriber(new CpoeEventSubscriber());
    }
}
