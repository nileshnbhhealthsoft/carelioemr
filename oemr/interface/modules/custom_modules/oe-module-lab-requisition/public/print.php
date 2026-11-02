<?php

require_once __DIR__ . '/_bootstrap.php';

use OpenEMR\Core\Header;
use OpenEMR\Modules\LabRequisition\OrderRepository;
use OpenEMR\Modules\LabRequisition\Sheet;

$pid = labreq_pid();
$order = labreq_load_order((int)($_GET['id'] ?? 0), $pid);
OrderRepository::event((int)$order['id'], labreq_user_id(), 'viewed');
$cancelled = $order['status'] === 'cancelled';
?>
<!DOCTYPE html>
<html>
<head>
    <title><?php echo xlt('Lab Requisition'); ?></title>
    <?php Header::setupHeader(['common']); ?>
    <link rel="stylesheet" href="assets/lab.css">
    <?php if ($cancelled) { ?><style>.labreq-sheet{opacity:.45}</style><?php } ?>
</head>
<body class="body_top">
<div class="labreq-page">
    <div class="labreq-toolbar">
        <?php if (!$cancelled) { ?>
            <button type="button" class="btn btn-primary" onclick="window.print()"><?php echo xlt('Print / Save PDF'); ?></button>
        <?php } ?>
        <?php if ($order['status'] === 'draft') { ?>
            <a class="btn btn-secondary" href="order.php?id=<?php echo attr_url($order['id']); ?>"><?php echo xlt('Edit'); ?></a>
        <?php } ?>
        <a class="btn btn-secondary" href="index.php"><?php echo xlt('Back to list'); ?></a>
        <?php if ($order['status'] !== 'cancelled') { ?>
            <form method="post" action="save.php" class="d-inline"
                  onsubmit="return confirm('<?php echo xla('Cancel this requisition?'); ?>');">
                <input type="hidden" name="csrf_token_form" value="<?php echo attr(labreq_csrf()); ?>">
                <input type="hidden" name="id" value="<?php echo attr((string)$order['id']); ?>">
                <button type="submit" name="do" value="cancel" class="btn btn-outline-danger"><?php echo xlt('Cancel requisition'); ?></button>
            </form>
        <?php } ?>
        <p class="note">
            <?php echo $cancelled ? xlt('This requisition was cancelled.') : xlt('On A4 paper, with margins set to default and scale 100%.'); ?>
            <?php if ($order['status'] === 'draft') { echo ' ' . xlt('Draft: not yet finalized.'); } ?>
        </p>
    </div>
    <?php (new Sheet($order, 'print', 'assets/carelio-logo.svg'))->render(); ?>
</div>
</body>
</html>
