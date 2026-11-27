<?php

namespace OpenEMR\Modules\CarelioCpoe\Event;

use OpenEMR\Events\Encounter\EncounterButtonEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Integrates Carelio CPOE Order Sets shortcut directly into OpenEMR's native Encounter toolbar.
 */
class CpoeEventSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            EncounterButtonEvent::BUTTON_RENDER => ['onEncounterButtonRender', 10],
        ];
    }

    public function onEncounterButtonRender(EncounterButtonEvent $event): void
    {
        $webRoot = $GLOBALS['webroot'] ?? '/carelioemr/oemr';
        $url = htmlspecialchars($webRoot . '/interface/modules/custom_modules/oe-module-carelio-cpoe/index.php');
        $btn = '<a href="' . $url . '" class="btn btn-info btn-sm ml-1" target="_blank" title="Open Carelio CPOE Order Sets & Critical Alerts">'
             . '<i class="fa fa-list-ol mr-1"></i> CPOE Order Sets</a>';
        $existing = (string)$event->displayButton();
        $event->setButton($existing . $btn);
    }
}
