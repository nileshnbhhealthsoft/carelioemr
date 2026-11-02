<?php

require_once __DIR__ . '/_bootstrap.php';

use OpenEMR\Core\Header;
use OpenEMR\Modules\LabRequisition\OrderRepository;

$pid = labreq_pid();
$statusLabel = ['draft' => xl('Draft'), 'final' => xl('Final'), 'cancelled' => xl('Cancelled')];
?>
<!DOCTYPE html>
<html>
<head>
    <title><?php echo xlt('Lab Requisition'); ?></title>
    <?php Header::setupHeader(['common']); ?>
</head>
<body class="body_top">
<div class="container-fluid mt-3">
    <h3><?php echo xlt('Lab Requisition'); ?></h3>
    <?php if ($pid <= 0) { ?>
        <div class="alert alert-info"><?php echo xlt('Select a patient first, then open this page again.'); ?></div>
    <?php } else {
        $orders = OrderRepository::listForPatient($pid);
        ?>
        <p><a class="btn btn-primary" href="order.php"><?php echo xlt('New lab requisition'); ?></a></p>
        <?php if (!$orders) { ?>
            <p class="text-muted"><?php echo xlt('No requisitions for this patient yet.'); ?></p>
        <?php } else { ?>
            <table class="table table-sm">
                <thead><tr>
                    <th>#</th><th><?php echo xlt('Date'); ?></th><th><?php echo xlt('Priority'); ?></th>
                    <th><?php echo xlt('Tests'); ?></th><th><?php echo xlt('Status'); ?></th><th></th>
                </tr></thead>
                <tbody>
                <?php foreach ($orders as $o) {
                    $priority = $o['is_stat'] ? 'STAT' : ($o['is_urgent'] ? xl('Urgent') : xl('Routine')); ?>
                    <tr>
                        <td><?php echo text($o['id']); ?></td>
                        <td><?php echo text($o['order_date'] ?: substr((string)$o['updated_at'], 0, 10)); ?></td>
                        <td><?php echo text($priority); ?></td>
                        <td><?php echo text($o['test_count']); ?></td>
                        <td><?php echo text($statusLabel[$o['status']] ?? $o['status']); ?></td>
                        <td>
                            <?php if ($o['status'] === 'draft') { ?>
                                <a href="order.php?id=<?php echo attr_url($o['id']); ?>"><?php echo xlt('Edit'); ?></a> |
                            <?php } ?>
                            <a href="print.php?id=<?php echo attr_url($o['id']); ?>"><?php echo xlt('View / Print'); ?></a>
                        </td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
        <?php } ?>
    <?php } ?>
</div>
</body>
</html>
