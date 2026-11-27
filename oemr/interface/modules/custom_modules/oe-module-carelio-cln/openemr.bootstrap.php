<?php

use OpenEMR\Core\OEGlobalsBag;
use OpenEMR\Menu\MenuEvent;
use OpenEMR\Modules\CarelioCln\Event\ClinicalEventSubscriber;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

spl_autoload_register(function ($class) {
    $clnPrefix = 'OpenEMR\\Modules\\CarelioCln\\';
    if (strncmp($clnPrefix, $class, strlen($clnPrefix)) === 0) {
        $rel = str_replace('\\', DIRECTORY_SEPARATOR, substr($class, strlen($clnPrefix))) . '.php';
        $local = __DIR__ . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . $rel;
        if (file_exists($local)) {
            require_once $local;
            return;
        }
    }

    $corePrefix = 'OpenEMR\\Modules\\CarelioCore\\';
    if (strncmp($corePrefix, $class, strlen($corePrefix)) === 0) {
        $rel = str_replace('\\', DIRECTORY_SEPARATOR, substr($class, strlen($corePrefix))) . '.php';
        $paths = [
            __DIR__ . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . $rel,
            dirname(__DIR__) . DIRECTORY_SEPARATOR . 'oe-module-carelio-core' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . $rel,
        ];
        foreach ($paths as $f) {
            if (file_exists($f)) {
                require_once $f;
                return;
            }
        }
    }
});

function oe_module_carelio_cln_add_menu_item(MenuEvent $event): MenuEvent
{
    $menu = $event->getMenu();
    $item = new stdClass();
    $item->requirement = 0;
    $item->target = 'hms_cln_mod';
    $item->menu_id = 'hms_cln_mod';
    $item->label = function_exists('xlt') ? xlt("Clinical Docs (CLN)") : "Clinical Docs (CLN)";
    $item->url = "/interface/modules/custom_modules/oe-module-carelio-cln/index.php";
    $item->children = [];
    $item->acl_req = ["encounters", "notes"];

    foreach ($menu as $top) {
        if (isset($top->menu_id) && $top->menu_id === 'mod_carelio_hms') {
            foreach ($top->children as $ch) {
                if (($ch->menu_id ?? '') === 'hms_cln_mod') {
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
    $dispatcher->addListener(MenuEvent::MENU_UPDATE, 'oe_module_carelio_cln_add_menu_item', 10);
    if (class_exists(ClinicalEventSubscriber::class)) {
        $dispatcher->addSubscriber(new ClinicalEventSubscriber());
    }
}
