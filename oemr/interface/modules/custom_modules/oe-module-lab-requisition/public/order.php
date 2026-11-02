<?php

require_once __DIR__ . '/_bootstrap.php';

use OpenEMR\Core\Header;
use OpenEMR\Modules\LabRequisition\Chart;
use OpenEMR\Modules\LabRequisition\OrderRepository;
use OpenEMR\Modules\LabRequisition\Sheet;

$pid = labreq_pid();
if ($pid <= 0) {
    header('Location: index.php');
    exit;
}
$id = (int)($_GET['id'] ?? 0);
if ($id > 0) {
    $order = labreq_load_order($id, $pid);
    if ($order['status'] !== 'draft') {
        header('Location: print.php?id=' . urlencode((string)$id));
        exit;
    }
} else {
    $order = Chart::defaults($pid, labreq_user_id(), labreq_encounter() ?: null) + ['specimen' => [], 'tests' => [], 'detail' => []];
}
$errors = $_GET['err'] ?? '';
?>
<!DOCTYPE html>
<html>
<head>
    <title><?php echo xlt('Lab Requisition'); ?></title>
    <?php Header::setupHeader(['common']); ?>
    <link rel="stylesheet" href="assets/lab.css">
</head>
<body class="body_top">
<div class="labreq-page">
<form method="post" action="save.php" id="labreq-form" autocomplete="off">
    <input type="hidden" name="csrf_token_form" value="<?php echo attr(labreq_csrf()); ?>">
    <input type="hidden" name="id" value="<?php echo attr((string)$id); ?>">
    <div class="labreq-toolbar">
        <button type="submit" name="do" value="save" class="btn btn-primary"><?php echo xlt('Save draft'); ?></button>
        <button type="submit" name="do" value="finalize" class="btn btn-success"><?php echo xlt('Finalize and print'); ?></button>
        <a class="btn btn-secondary" href="index.php"><?php echo xlt('Back to list'); ?></a>
        <p class="note"><?php echo xlt('Finalizing locks the requisition and records you as the ordering user. A draft can be edited any time.'); ?></p>
    </div>
    <?php if ($errors !== '' || isset($_GET['saved'])) { ?>
        <div class="labreq-alert alert <?php echo $errors !== '' ? 'alert-danger' : 'alert-success'; ?>">
            <?php echo $errors !== '' ? text((string)$errors) : xlt('Saved'); ?>
        </div>
    <?php } ?>
    <?php (new Sheet($order, 'edit', 'assets/carelio-logo.svg'))->render(); ?>
</form>
</div>
</body>
</html>
