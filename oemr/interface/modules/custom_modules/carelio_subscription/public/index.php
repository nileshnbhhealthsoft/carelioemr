<?php

/**
 * Carelio Subscription dashboard.
 */

require_once(__DIR__ . "/../../../../globals.php");

use OpenEMR\Common\Acl\AccessDeniedHelper;
use OpenEMR\Common\Acl\AclMain;
use OpenEMR\Core\Header;

if (!AclMain::aclCheckCore('admin', 'users')) {
    AccessDeniedHelper::denyWithTemplate("ACL check failed for admin/users: Carelio Subscription", xl("Carelio Subscription"));
}

$summary = [
    'active' => 0,
    'past_due' => 0,
    'canceled' => 0,
    'total' => 0,
];

if (function_exists('sqlQuery')) {
    try {
        $row = sqlQuery("SELECT
            SUM(CASE WHEN `status` = 'active' THEN 1 ELSE 0 END) AS active_count,
            SUM(CASE WHEN `status` = 'past_due' THEN 1 ELSE 0 END) AS past_due_count,
            SUM(CASE WHEN `status` = 'canceled' THEN 1 ELSE 0 END) AS canceled_count,
            COUNT(*) AS total_count
            FROM `mod_carelio_subscriptions`");
        if (is_array($row)) {
            $summary = [
                'active' => (int) ($row['active_count'] ?? 0),
                'past_due' => (int) ($row['past_due_count'] ?? 0),
                'canceled' => (int) ($row['canceled_count'] ?? 0),
                'total' => (int) ($row['total_count'] ?? 0),
            ];
        }
    } catch (\Throwable $e) {
        error_log('Carelio Subscription dashboard warning: ' . $e->getMessage());
    }
}
?>
<!doctype html>
<html>
<head>
    <?php Header::setupHeader(); ?>
    <title><?php echo xlt('Carelio Subscription'); ?></title>
    <style>
        .carelio-subscription-wrap { padding: 24px; }
        .carelio-subscription-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 12px; }
        .carelio-subscription-card { background: #fff; border: 1px solid #dbe4ee; border-radius: 8px; padding: 18px; }
        .carelio-subscription-card strong { display: block; font-size: 28px; color: #0f172a; }
        .carelio-subscription-card span { color: #64748b; font-size: 13px; }
    </style>
</head>
<body>
<div class="carelio-subscription-wrap">
    <h1><?php echo xlt('Carelio Subscription'); ?></h1>
    <p class="text-muted"><?php echo xlt('Independent subscription module. Site Admin Config remains separate.'); ?></p>

    <div class="carelio-subscription-grid">
        <div class="carelio-subscription-card">
            <strong><?php echo text((string) $summary['active']); ?></strong>
            <span><?php echo xlt('Active'); ?></span>
        </div>
        <div class="carelio-subscription-card">
            <strong><?php echo text((string) $summary['past_due']); ?></strong>
            <span><?php echo xlt('Past Due'); ?></span>
        </div>
        <div class="carelio-subscription-card">
            <strong><?php echo text((string) $summary['canceled']); ?></strong>
            <span><?php echo xlt('Canceled'); ?></span>
        </div>
        <div class="carelio-subscription-card">
            <strong><?php echo text((string) $summary['total']); ?></strong>
            <span><?php echo xlt('Total'); ?></span>
        </div>
    </div>
</div>
</body>
</html>
