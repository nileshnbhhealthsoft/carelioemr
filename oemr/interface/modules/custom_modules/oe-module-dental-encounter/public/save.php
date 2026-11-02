<?php
define('DENTAL_ENCOUNTER_JSON_REQUEST', true);
require_once __DIR__ . '/../common.php';

use Carelio\DentalEncounter\Encounter;
use Carelio\DentalEncounter\Module;
use Carelio\DentalEncounter\Payload;

header('Content-Type: application/json; charset=utf-8');
try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Allow: POST');
        throw new RuntimeException('POST is required.', 405);
    }
    if (!Module::verifyCsrfToken($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '')) {
        throw new RuntimeException('Session verification failed. Reopen the form and try again.', 403);
    }
    $raw = file_get_contents('php://input', false, null, 0, 1048577);
    if (strlen($raw) > 1048576) {
        throw new RuntimeException('Record exceeds the 1 MB limit.', 413);
    }
    $request = json_decode($raw, true, 64, JSON_THROW_ON_ERROR);
    foreach (['pid', 'encounter', 'id', 'revision'] as $key) {
        if (!isset($request[$key]) || !is_int($request[$key]) || $request[$key] < 0) {
            throw new InvalidArgumentException('Invalid request identifiers.');
        }
    }
    if ($request['pid'] !== Module::patientId()
        || $request['encounter'] !== Module::encounterId()) {
        throw new RuntimeException('The active patient or encounter changed. Reopen the correct encounter before saving.', 409);
    }
    $context = Encounter::context($request['pid'], $request['encounter']);
    if (!is_array($request['data'] ?? null)) {
        throw new InvalidArgumentException('Missing encounter data.');
    }
    $data = Payload::validate($request['data']);
    $result = Encounter::save($context, $request['id'], $request['revision'], $data);
    echo json_encode(['ok' => true] + $result, JSON_THROW_ON_ERROR);
} catch (Throwable $error) {
    $status = $error instanceof InvalidArgumentException || $error instanceof JsonException ? 422 : $error->getCode();
    if (!in_array($status, [400, 403, 404, 405, 409, 413, 422, 423], true)) {
        $status = 500;
        error_log('Dental encounter save failed: ' . get_class($error) . ': ' . $error->getMessage()
            . ' at ' . $error->getFile() . ':' . $error->getLine());
    }
    http_response_code($status);
    echo json_encode(['ok' => false, 'error' => $status === 500
        ? 'Unable to save. Your entries are still on this page; check the server log before retrying.'
        : $error->getMessage()]);
}
