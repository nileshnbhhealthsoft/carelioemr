<?php

require_once __DIR__ . '/_bootstrap.php';

use OpenEMR\Modules\ClaimForms\ClaimRepository;
use OpenEMR\Modules\ClaimForms\Storage;

$pid = claimforms_pid();
$claim = ClaimRepository::get((int)($_GET['id'] ?? 0));
if (!$claim || (int)$claim['pid'] !== $pid || empty($claim['pdf_path'])) {
    http_response_code(404);
    echo xlt('Not found');
    exit;
}
// pdf_path is a bare file name written by this module; basename() makes sure it cannot point elsewhere.
$file = Storage::claimDir($pid) . '/' . basename((string)$claim['pdf_path']);
if (!is_file($file)) {
    http_response_code(404);
    echo xlt('Not found');
    exit;
}
ClaimRepository::event((int)$claim['id'], claimforms_user_id(), 'downloaded');
header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="claim_' . (int)$claim['id'] . '.pdf"');
header('Content-Length: ' . filesize($file));
header('Cache-Control: private, no-store');
readfile($file);
