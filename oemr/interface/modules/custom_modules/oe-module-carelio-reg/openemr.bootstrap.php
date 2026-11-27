<?php

use OpenEMR\Core\OEGlobalsBag;
use OpenEMR\Menu\MenuEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

spl_autoload_register(function ($class) {
    $prefix = 'OpenEMR\\Modules\\CarelioCore\\';
    if (strncmp($prefix, $class, strlen($prefix)) === 0) {
        $rel = str_replace('\\', DIRECTORY_SEPARATOR, substr($class, strlen($prefix))) . '.php';
        $local = __DIR__ . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . $rel;
        if (file_exists($local)) {
            require_once $local;
        }
    }
});

function oe_module_carelio_reg_add_menu_item(MenuEvent $event): MenuEvent
{
    $menu = $event->getMenu();
    $item = new stdClass();
    $item->requirement = 0;
    $item->target = 'hms_reg_mod';
    $item->menu_id = 'hms_reg_mod';
    $item->label = function_exists('xlt') ? xlt("Registration (REG)") : "Registration (REG)";
    $item->url = "/interface/modules/custom_modules/oe-module-carelio-reg/index.php";
    $item->children = [];
    $item->acl_req = ["patients", "demo"];

    foreach ($menu as $top) {
        if (isset($top->menu_id) && $top->menu_id === 'mod_carelio_hms') {
            foreach ($top->children as $ch) {
                if (($ch->menu_id ?? '') === 'hms_reg_mod') {
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
    $dispatcher->addListener(MenuEvent::MENU_UPDATE, 'oe_module_carelio_reg_add_menu_item', 10);
}
