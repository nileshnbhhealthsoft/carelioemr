<?php

namespace OpenEMR\Modules\ClaimForms;

use OpenEMR\Menu\MenuEvent;
use OpenEMR\Menu\PatientMenuEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Wires the module into OpenEMR: a Patient menu entry and a patient dashboard tab.
 * All pages live under public/ and re-check authorization themselves.
 */
class Bootstrap
{
    public const MODULE_DIR = 'oe-module-claim-forms';
    public const MODULE_URL = '/interface/modules/custom_modules/' . self::MODULE_DIR . '/public/';

    public function __construct(private EventDispatcherInterface $dispatcher)
    {
    }

    public function subscribeToEvents(): void
    {
        $this->dispatcher->addListener(MenuEvent::MENU_UPDATE, [$this, 'addMenuItem']);
        $this->dispatcher->addListener(PatientMenuEvent::MENU_UPDATE, [$this, 'addPatientMenuItem']);
    }

    public function addMenuItem(MenuEvent $event): MenuEvent
    {
        $menu = $event->getMenu();

        $item = new \stdClass();
        $item->requirement = 0;
        $item->target = 'mod';
        $item->menu_id = 'claimforms0';
        $item->label = xlt('Insurance Claim Forms');
        $item->url = self::MODULE_URL . 'index.php';
        $item->children = [];
        $item->acl_req = ['patients', 'med'];
        $item->global_req = [];

        // Attach under the Patient menu. The id differs between versions, so try the known ones.
        $placed = false;
        foreach ($menu as $top) {
            if (in_array($top->menu_id ?? '', ['patimg', 'patmen'], true)) {
                $top->children[] = $item;
                $placed = true;
                break;
            }
        }
        if (!$placed) {
            $menu[] = $item;
        }
        $event->setMenu($menu);
        return $event;
    }

    /** Adds a "Claim Forms" tab to the patient dashboard's tab bar. */
    public function addPatientMenuItem(PatientMenuEvent $event): PatientMenuEvent
    {
        $item = new \stdClass();
        $item->label = xlt('Claim Forms');
        $item->menu_id = 'claimforms_pt';
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
