<?php

namespace OpenEMR\Modules\RadiologyRequisition;

use OpenEMR\Events\Encounter\EncounterMenuEvent;
use OpenEMR\Menu\PatientMenuEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Wires the module into OpenEMR: an entry in the encounter screen's Orders menu and a patient dashboard tab.
 * All pages live under public/ and re-check authorization themselves.
 */
class Bootstrap
{
    public const MODULE_DIR = 'oe-module-radiology-requisition';
    public const MODULE_URL = '/interface/modules/custom_modules/' . self::MODULE_DIR . '/public/';

    public function __construct(private EventDispatcherInterface $dispatcher)
    {
    }

    public function subscribeToEvents(): void
    {
        // Negative priority: run after the core listeners that build the encounter screen's category menus.
        $this->dispatcher->addListener(EncounterMenuEvent::MENU_RENDER, [$this, 'addEncounterMenuItem'], -10);
        $this->dispatcher->addListener(PatientMenuEvent::MENU_UPDATE, [$this, 'addPatientMenuItem']);
    }

    /** Adds "Radiology Requisition" to the Orders menu on the encounter screen. */
    public function addEncounterMenuItem(EncounterMenuEvent $event): EncounterMenuEvent
    {
        $menu = $event->getMenuData();
        $category = xl('Orders');
        $menu[$category]['children'][] = [
            'menu_name' => xl('Radiology Requisition'),
            'href' => ($GLOBALS['webroot'] ?? '') . self::MODULE_URL . 'index.php',
        ];
        $event->setMenuData($menu);
        return $event;
    }

    /** Adds a "Radiology Requisition" tab to the patient dashboard's tab bar. */
    public function addPatientMenuItem(PatientMenuEvent $event): PatientMenuEvent
    {
        $item = new \stdClass();
        $item->label = xlt('Radiology Requisition');
        $item->menu_id = 'radreq_pt';
        $item->target = 'main';
        $item->on_click = 'top.restoreSession()';
        $item->url = ($GLOBALS['webroot'] ?? '') . self::MODULE_URL . 'index.php';
        $item->pid = 'false';
        $item->children = [];
        $item->requirement = 0;
        $item->acl_req = ['patients', 'med'];

        $menu = $event->getMenu();
        $menu[] = $item;
        $event->setMenu($menu);
        return $event;
    }
}
