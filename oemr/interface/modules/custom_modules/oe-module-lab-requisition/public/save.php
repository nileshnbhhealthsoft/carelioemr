<?php

require_once __DIR__ . '/_bootstrap.php';

use OpenEMR\Modules\LabRequisition\Chart;
use OpenEMR\Modules\LabRequisition\OrderRepository;
use OpenEMR\Modules\LabRequisition\Sanitizer;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    header('Location: index.php');
    exit;
}
labreq_verify_csrf($_POST['csrf_token_form'] ?? null);

$pid = labreq_pid();
$uid = labreq_user_id();
if ($pid <= 0 || $uid <= 0) {
    header('Location: index.php');
    exit;
}

$do = (string)($_POST['do'] ?? 'save');
$id = (int)($_POST['id'] ?? 0);
$existing = $id > 0 ? labreq_load_order($id, $pid) : null;

if ($do === 'cancel') {
    if ($existing) {
        OrderRepository::cancel($id, $uid);
    }
    header('Location: index.php');
    exit;
}

if ($existing && $existing['status'] !== 'draft') {
    // Finalized and cancelled requisitions are read-only.
    header('Location: print.php?id=' . urlencode((string)$id));
    exit;
}

$clean = Sanitizer::order($_POST);
if ($do === 'finalize') {
    $errors = Sanitizer::finalizeErrors($clean);
    if ($errors) {
        // Keep what was typed: save it as a draft, then send the user back with the reasons.
        $id = $existing ? $id : 0;
        $do = 'save';
        $msg = implode(' ', array_map('xl', $errors));
    }
}

if ($existing) {
    OrderRepository::update($id, $clean, $uid);
} else {
    $encounter = labreq_encounter();
    $encounter = $encounter > 0 && Chart::encounterBelongs($pid, $encounter) ? $encounter : null;
    $id = OrderRepository::create($pid, $encounter, $clean, $uid);
}

if ($do === 'finalize') {
    OrderRepository::finalize($id, $uid, Chart::userName($uid));
    header('Location: print.php?id=' . urlencode((string)$id));
    exit;
}
$qs = 'id=' . urlencode((string)$id) . (isset($msg) ? '&err=' . urlencode($msg) : '&saved=1');
header('Location: order.php?' . $qs);
exit;
