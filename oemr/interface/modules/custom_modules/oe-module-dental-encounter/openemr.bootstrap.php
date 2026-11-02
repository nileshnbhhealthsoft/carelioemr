<?php
/**
 * Loaded by OpenEMR only for enabled modules. No core files need changing.
 */
use OpenEMR\Events\Encounter\LoadEncounterFormFilterEvent;

if (isset($eventDispatcher)) {
    $eventDispatcher->addListener(
        LoadEncounterFormFilterEvent::EVENT_NAME,
        static function (LoadEncounterFormFilterEvent $event): void {
            if ($event->getFormName() === 'dental_encounter'
                && in_array($event->getPageName(), ['new.php', 'view.php', 'report.php'], true)) {
                $event->setDir(__DIR__ . '/forms/dental_encounter/');
            }
        }
    );
}
