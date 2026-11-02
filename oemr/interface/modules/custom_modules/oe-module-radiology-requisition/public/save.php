<?php

require_once __DIR__ . '/_bootstrap.php';

use OpenEMR\Modules\RadiologyRequisition\Chart;
use OpenEMR\Modules\RadiologyRequisition\OrderRepository;
use OpenEMR\Modules\RadiologyRequisition\Sanitizer;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    header('Location: index.php');
    exit;
}
radreq_verify_csrf($_POST['csrf_token_form'] ?? null);

$pid = radreq_pid();
$uid = radreq_user_id();
if ($pid <= 0 || $uid <= 0) {
    header('Location: index.php');
    exit;
}

$do = (string)($_POST['do'] ?? 'save');
$id = (int)($_POST['id'] ?? 0);
$existing = $id > 0 ? radreq_load_order($id, $pid) : null;

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
$problems = [];
if ($do === 'finalize') {
    $problems = Sanitizer::finalizeErrors($clean, !empty($_POST['ack_unknown']));
}

if ($existing) {
    OrderRepository::update($id, $clean, $uid);
} else {
    $encounter = radreq_encounter();
    $encounter = $encounter > 0 && Chart::encounterBelongs($pid, $encounter) ? $encounter : null;
    $id = OrderRepository::create($pid, $encounter, $clean, $uid);
}

if ($do === 'finalize' && !$problems) {
    OrderRepository::finalize($id, $uid);
    header('Location: print.php?id=' . urlencode((string)$id));
    exit;
}
// Whatever was typed is kept as a draft; a failed finalize sends the user back with the reasons.
$qs = 'id=' . urlencode((string)$id) . ($problems ? '&err=' . urlencode(implode("\n", array_map('xl', $problems))) : '&saved=1');
header('Location: order.php?' . $qs);
exit;
