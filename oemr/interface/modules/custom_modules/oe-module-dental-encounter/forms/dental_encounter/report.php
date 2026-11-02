<?php
require_once __DIR__ . '/../../common.php';
require_once __DIR__ . '/../../src/Report.php';

use Carelio\DentalEncounter\Encounter;
use Carelio\DentalEncounter\Report;

function dental_encounter_report($pid, $encounter, $cols, $id): void
{
    try {
        Encounter::context((int)$pid, (int)$encounter);
        $record = Encounter::load((int)$pid, (int)$encounter, (int)$id);
        if (!$record) {
            echo text('Dental record unavailable.');
            return;
        }
        $data = json_decode($record['payload'], true, 64, JSON_THROW_ON_ERROR);
        echo Report::render($data, (int)$record['revision']);
    } catch (Throwable $error) {
        echo text('Dental record unavailable or access denied.');
    }
}
