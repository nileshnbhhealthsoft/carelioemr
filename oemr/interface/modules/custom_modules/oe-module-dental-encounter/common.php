<?php
/** All HTTP entry points use OpenEMR authentication, session and database setup. */
require_once dirname(__DIR__, 3) . '/globals.php';
require_once $GLOBALS['srcdir'] . '/ESign/Api.php';
require_once __DIR__ . '/src/Payload.php';
require_once __DIR__ . '/src/Encounter.php';
require_once __DIR__ . '/src/Module.php';

use Carelio\DentalEncounter\Module;

if (!headers_sent()) {
    header('Cache-Control: no-store, private');
    header('Pragma: no-cache');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: same-origin');
}
try {
    Module::requireEnabled();
} catch (Throwable $error) {
    $status = in_array($error->getCode(), [401, 403, 503], true) ? $error->getCode() : 500;
    $message = $status === 500 ? 'Unable to check dental module registration. Check the OpenEMR server log.'
        : $error->getMessage();
    if ($status === 500) {
        error_log('Dental module registration check failed: ' . get_class($error));
    }
    http_response_code($status);
    if (defined('DENTAL_ENCOUNTER_JSON_REQUEST')) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'error' => $message]);
    } else {
        echo text($message);
    }
    exit;
}
if (!defined('DENTAL_ENCOUNTER_BOOTSTRAPPED')) {
    define('DENTAL_ENCOUNTER_BOOTSTRAPPED', true);
}
