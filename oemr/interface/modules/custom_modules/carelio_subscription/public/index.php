<?php

/**
 * Carelio Subscription Management Module Dashboard
 */

require_once(__DIR__ . "/../../../../globals.php");

use OpenEMR\Common\Acl\AclMain;
use OpenEMR\Common\Csrf\CsrfUtils;
use OpenEMR\Common\Session\SessionWrapperFactory;
use OpenEMR\Core\Header;
use OpenEMR\Modules\CarelioSubscription\Services\SubscriptionManagerService;

$session = SessionWrapperFactory::getInstance()->getActiveSession();
$userId = (int) ($session->get('authUserID') ?? 0);
$username = (string) ($session->get('authUser') ?? 'Admin');
$isSiteAdmin = AclMain::aclCheckCore('admin', 'users');

$feedback = null;
$feedbackType = 'info';

// Process Actions (Only allowed for Site Administrator)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isSiteAdmin) {
    if (!empty($_POST['csrf_token_form'])) {
        try {
            CsrfUtils::checkCsrfInput(INPUT_POST, dieOnFail: false);
        } catch (\Throwable) {
        }
    }

    $subId = (int) ($_POST['sub_id'] ?? 0);
    $action = (string) ($_POST['form_action'] ?? '');

    if ($action === 'renew') {
        $months = max(1, (int) ($_POST['renew_months'] ?? 1));
        if (SubscriptionManagerService::renewSubscription($subId, $username, $months)) {
            $feedback = xl("Subscription successfully renewed for {$months} month(s)!");
            $feedbackType = 'success';
        }
    } elseif ($action === 'extend') {
        $newDate = trim((string) ($_POST['new_date'] ?? ''));
        if (!empty($newDate) && SubscriptionManagerService::extendExpiration($subId, $username, $newDate)) {
            $feedback = xl("Subscription expiration date successfully updated to: ") . htmlspecialchars($newDate);
            $feedbackType = 'success';
        } else {
            $feedback = xl("Invalid expiration date provided.");
            $feedbackType = 'danger';
        }
    } elseif ($action === 'reactivate') {
        if (SubscriptionManagerService::reactivateSubscription($subId, $username, "Reactivated by {$username}")) {
            $feedback = xl("Subscription successfully reactivated and restored to ACTIVE status!");
            $feedbackType = 'success';
        }
    } elseif ($action === 'simulate') {
        $targetStatus = (string) ($_POST['target_status'] ?? '');
        if (SubscriptionManagerService::simulateStatus($subId, $targetStatus, $username)) {
            $feedback = xl("Simulation Applied: Status changed to ") . "<strong>" . htmlspecialchars($targetStatus) . "</strong>";
            $feedbackType = 'info';
        }
    }
}

$sub = SubscriptionManagerService::getCurrentSubscription();
$history = !empty($sub['id']) ? SubscriptionManagerService::getHistory((int) $sub['id']) : [];

$status = strtoupper((string) ($sub['status'] ?? 'ACTIVE'));
$csrfToken = '';
try {
    $csrfToken = CsrfUtils::collectCsrfToken(session: $session);
} catch (\Throwable) {
    if (empty($session->get('csrf_private_key'))) {
        CsrfUtils::setupCsrfKey($session);
    }
    $csrfToken = CsrfUtils::collectCsrfToken(session: $session);
}
?>
<!doctype html>
<html>
<head>
    <?php Header::setupHeader(); ?>
    <title><?php echo xlt('Carelio Subscription Management'); ?></title>
    <style>
        body { background: #f8fafc; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; color: #1e293b; }
        .carelio-wrap { max-width: 1200px; margin: 20px auto; padding: 0 20px; }
        .carelio-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; padding-bottom: 16px; border-bottom: 1px solid #e2e8f0; }
        .carelio-title { font-size: 1.6rem; font-weight: 700; color: #0f172a; margin: 0; }
        .carelio-subtitle { font-size: 0.9rem; color: #64748b; margin-top: 4px; }
        .badge-status { font-size: 0.85rem; padding: 6px 14px; border-radius: 9999px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }
        .status-ACTIVE { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .status-EXPIRING_SOON { background: #fef9c3; color: #854d0e; border: 1px solid #fef08a; }
        .status-EXPIRED_GRACE_PERIOD { background: #ffedd5; color: #9a3412; border: 1px solid #fed7aa; }
        .status-DEACTIVATED { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        .card-box { background: #fff; border: 1px solid #e2e8f0; border-radius: 10px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); padding: 20px; margin-bottom: 24px; }
        .banner-alert { border-radius: 8px; padding: 16px 20px; margin-bottom: 24px; display: flex; align-items: center; gap: 14px; font-size: 0.95rem; }
        .banner-ACTIVE { background: #f0fdf4; border-left: 5px solid #22c55e; color: #15803d; }
        .banner-EXPIRING_SOON { background: #fefce8; border-left: 5px solid #eab308; color: #a16207; }
        .banner-EXPIRED_GRACE_PERIOD { background: #fff7ed; border-left: 5px solid #f97316; color: #c2410c; }
        .banner-DEACTIVATED { background: #fef2f2; border-left: 5px solid #ef4444; color: #b91c1c; }
        .grid-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 24px; }
        .stat-item { background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 18px; }
        .stat-label { font-size: 0.8rem; text-transform: uppercase; color: #64748b; font-weight: 600; letter-spacing: 0.5px; }
        .stat-value { font-size: 1.35rem; font-weight: 700; color: #0f172a; margin-top: 6px; }
        .sim-panel { background: #f1f5f9; border: 1px dashed #cbd5e1; border-radius: 8px; padding: 16px 20px; margin-bottom: 24px; }
        .sim-title { font-size: 0.9rem; font-weight: 700; color: #475569; text-transform: uppercase; margin-bottom: 10px; display: flex; align-items: center; gap: 6px; }
        .sim-btns { display: flex; gap: 10px; flex-wrap: wrap; }
        .btn-sim { padding: 6px 12px; font-size: 0.82rem; font-weight: 600; border-radius: 6px; cursor: pointer; border: 1px solid; }
        .table th { font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.5px; color: #64748b; }
        .table td { vertical-align: middle; font-size: 0.9rem; }
    </style>
</head>
<body>
<div class="carelio-wrap">

    <!-- Header -->
    <div class="carelio-header">
        <div>
            <h1 class="carelio-title"><?php echo xlt('Carelio Subscription Management'); ?></h1>
            <div class="carelio-subtitle">
                <?php echo xlt('Tenant Site:'); ?> <strong><?php echo htmlspecialchars($sub['site_id'] ?? 'default'); ?></strong>
                &bull; <?php echo xlt('Customer:'); ?> <strong><?php echo htmlspecialchars($sub['customer_name'] ?? 'Site Administrator'); ?></strong>
            </div>
        </div>
        <div>
            <span class="badge-status status-<?php echo attr($status); ?>">
                <i class="fa fa-circle mr-1"></i> <?php echo htmlspecialchars(str_replace('_', ' ', $status)); ?>
            </span>
        </div>
    </div>

    <!-- Alert / Feedback Notification -->
    <?php if ($feedback): ?>
        <div class="alert alert-<?php echo attr($feedbackType); ?> alert-dismissible fade show" role="alert">
            <?php echo $feedback; ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    <?php endif; ?>

    <!-- Status Banner Message -->
    <?php if ($status === 'ACTIVE'): ?>
        <div class="banner-alert banner-ACTIVE">
            <i class="fa fa-check-circle fa-2x"></i>
            <div>
                <strong><?php echo xlt('Subscription Active'); ?></strong> &mdash; 
                <?php echo xlt('Your CarelioEMR account is fully active. Next renewal is due on:'); ?> 
                <strong><?php echo htmlspecialchars(date('M d, Y', strtotime($sub['current_period_end'] ?? 'now'))); ?></strong>.
            </div>
        </div>
    <?php elseif ($status === 'EXPIRING_SOON'): ?>
        <div class="banner-alert banner-EXPIRING_SOON">
            <i class="fa fa-exclamation-triangle fa-2x"></i>
            <div>
                <strong><?php echo xlt('Renewal Reminder: Subscription Expiring Soon'); ?></strong> &mdash; 
                <?php echo xlt('Your subscription will expire on:'); ?> 
                <strong><?php echo htmlspecialchars(date('M d, Y H:i', strtotime($sub['current_period_end'] ?? 'now'))); ?></strong> 
                (<?php echo xlt('within 2 days'); ?>). <?php echo xlt('Please renew below to prevent service disruption.'); ?>
            </div>
        </div>
    <?php elseif ($status === 'EXPIRED_GRACE_PERIOD'): ?>
        <div class="banner-alert banner-EXPIRED_GRACE_PERIOD">
            <i class="fa fa-clock-o fa-2x"></i>
            <div>
                <strong><?php echo xlt('Grace Period Active'); ?></strong> &mdash; 
                <?php echo xlt('Your subscription expired on'); ?> 
                <strong><?php echo htmlspecialchars(date('M d, Y', strtotime($sub['current_period_end'] ?? 'now'))); ?></strong>. 
                <?php echo xlt('You have a 2-day grace period ending on:'); ?> 
                <strong><?php echo htmlspecialchars(!empty($sub['grace_ends_at']) ? date('M d, Y H:i', strtotime($sub['grace_ends_at'])) : 'Within 48 hours'); ?></strong>. 
                <?php echo xlt('Renew now before the site is automatically deactivated.'); ?>
            </div>
        </div>
    <?php elseif ($status === 'DEACTIVATED'): ?>
        <div class="banner-alert banner-DEACTIVATED">
            <i class="fa fa-ban fa-2x"></i>
            <div>
                <strong><?php echo xlt('Site Deactivated (Grace Period Expired)'); ?></strong> &mdash; 
                <?php echo xlt('The 2-day grace period has ended. Clinical, patient, and billing data remain 100% safe.'); ?> 
                <?php echo xlt('Site Administrator can reactivate and restore full access instantly below.'); ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Interactive Simulation / Quick Test Toolbar (Requested for testing) -->
    <?php if ($isSiteAdmin): ?>
    <div class="sim-panel">
        <div class="sim-title">
            <i class="fa fa-flask"></i> <?php echo xlt('Subscription Status Simulator (Quick Test Toolbar)'); ?>
        </div>
        <p class="small text-muted mb-2">
            <?php echo xlt('Test each lifecycle phase instantly without waiting for real calendar dates:'); ?>
        </p>
        <div class="sim-btns">
            <form method="post" class="d-inline">
                <input type="hidden" name="csrf_token_form" value="<?php echo attr($csrfToken); ?>">
                <input type="hidden" name="sub_id" value="<?php echo attr($sub['id'] ?? 0); ?>">
                <input type="hidden" name="form_action" value="simulate">
                <input type="hidden" name="target_status" value="ACTIVE">
                <button type="submit" class="btn btn-sm btn-outline-success btn-sim">
                    <i class="fa fa-play mr-1"></i> 1. <?php echo xlt('Test ACTIVE (+30 Days)'); ?>
                </button>
            </form>

            <form method="post" class="d-inline">
                <input type="hidden" name="csrf_token_form" value="<?php echo attr($csrfToken); ?>">
                <input type="hidden" name="sub_id" value="<?php echo attr($sub['id'] ?? 0); ?>">
                <input type="hidden" name="form_action" value="simulate">
                <input type="hidden" name="target_status" value="EXPIRING_SOON">
                <button type="submit" class="btn btn-sm btn-outline-warning btn-sim">
                    <i class="fa fa-bell mr-1"></i> 2. <?php echo xlt('Test EXPIRING SOON (<= 2 Days)'); ?>
                </button>
            </form>

            <form method="post" class="d-inline">
                <input type="hidden" name="csrf_token_form" value="<?php echo attr($csrfToken); ?>">
                <input type="hidden" name="sub_id" value="<?php echo attr($sub['id'] ?? 0); ?>">
                <input type="hidden" name="form_action" value="simulate">
                <input type="hidden" name="target_status" value="EXPIRED_GRACE_PERIOD">
                <button type="submit" class="btn btn-sm btn-outline-secondary btn-sim" style="border-color:#f97316;color:#c2410c;">
                    <i class="fa fa-hourglass-half mr-1"></i> 3. <?php echo xlt('Test GRACE PERIOD (Day 1-2)'); ?>
                </button>
            </form>

            <form method="post" class="d-inline">
                <input type="hidden" name="csrf_token_form" value="<?php echo attr($csrfToken); ?>">
                <input type="hidden" name="sub_id" value="<?php echo attr($sub['id'] ?? 0); ?>">
                <input type="hidden" name="form_action" value="simulate">
                <input type="hidden" name="target_status" value="DEACTIVATED">
                <button type="submit" class="btn btn-sm btn-outline-danger btn-sim">
                    <i class="fa fa-lock mr-1"></i> 4. <?php echo xlt('Test DEACTIVATED (> 2 Days)'); ?>
                </button>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- Summary Key Metrics -->
    <div class="grid-stats">
        <div class="stat-item">
            <div class="stat-label"><?php echo xlt('Current Plan'); ?></div>
            <div class="stat-value"><?php echo htmlspecialchars($sub['plan_name'] ?? 'Starter Plan'); ?></div>
            <div class="small text-muted mt-1">$<?php echo htmlspecialchars(number_format((float)($sub['plan_amount'] ?? 80), 2)); ?> / <?php echo htmlspecialchars($sub['billing_cycle'] ?? 'monthly'); ?></div>
        </div>

        <div class="stat-item">
            <div class="stat-label"><?php echo xlt('Period Start'); ?></div>
            <div class="stat-value" style="font-size:1.15rem;">
                <?php echo htmlspecialchars(!empty($sub['current_period_start']) ? date('M d, Y', strtotime($sub['current_period_start'])) : '-'); ?>
            </div>
            <div class="small text-muted mt-1"><?php echo xlt('Billing cycle started'); ?></div>
        </div>

        <div class="stat-item">
            <div class="stat-label"><?php echo xlt('Expiration Date'); ?></div>
            <div class="stat-value" style="font-size:1.15rem; color:<?php echo ($status !== 'ACTIVE') ? '#dc2626' : '#0f172a'; ?>;">
                <?php echo htmlspecialchars(!empty($sub['current_period_end']) ? date('M d, Y', strtotime($sub['current_period_end'])) : '-'); ?>
            </div>
            <div class="small text-muted mt-1">
                <?php 
                if (!empty($sub['current_period_end'])) {
                    $diff = round((strtotime($sub['current_period_end']) - time()) / 86400);
                    echo ($diff >= 0) ? "{$diff} " . xlt('day(s) remaining') : abs($diff) . " " . xlt('day(s) ago');
                }
                ?>
            </div>
        </div>

        <div class="stat-item">
            <div class="stat-label"><?php echo xlt('Grace Period Ends'); ?></div>
            <div class="stat-value" style="font-size:1.15rem;">
                <?php echo htmlspecialchars(!empty($sub['grace_ends_at']) ? date('M d, Y', strtotime($sub['grace_ends_at'])) : (!empty($sub['current_period_end']) ? date('M d, Y', strtotime('+2 days', strtotime($sub['current_period_end']))) : '-')); ?>
            </div>
            <div class="small text-muted mt-1"><?php echo xlt('2 Days Grace Window'); ?></div>
        </div>
    </div>

    <!-- Actions & Renewal Box (Site Administrator Only) -->
    <?php if ($isSiteAdmin): ?>
    <div class="card-box">
        <h5 class="mb-3 font-weight-bold"><i class="fa fa-cogs mr-2 text-primary"></i> <?php echo xlt('Site Administrator Actions'); ?></h5>
        <div class="row align-items-center">
            <!-- Renew Action -->
            <div class="col-md-4 mb-3 mb-md-0">
                <form method="post" onsubmit="return confirm('<?php echo xla('Are you sure you want to renew this subscription for 1 month?'); ?>');">
                    <input type="hidden" name="csrf_token_form" value="<?php echo attr($csrfToken); ?>">
                    <input type="hidden" name="sub_id" value="<?php echo attr($sub['id'] ?? 0); ?>">
                    <input type="hidden" name="form_action" value="renew">
                    <input type="hidden" name="renew_months" value="1">
                    <button type="submit" class="btn btn-success btn-block py-2">
                        <i class="fa fa-refresh mr-1"></i> <?php echo xlt('Renew Subscription (+1 Month)'); ?>
                    </button>
                </form>
            </div>

            <!-- Reactivate Action (If Deactivated) -->
            <?php if ($status === 'DEACTIVATED'): ?>
            <div class="col-md-4 mb-3 mb-md-0">
                <form method="post" onsubmit="return confirm('<?php echo xla('Reactivate this site and restore full access?'); ?>');">
                    <input type="hidden" name="csrf_token_form" value="<?php echo attr($csrfToken); ?>">
                    <input type="hidden" name="sub_id" value="<?php echo attr($sub['id'] ?? 0); ?>">
                    <input type="hidden" name="form_action" value="reactivate">
                    <button type="submit" class="btn btn-primary btn-block py-2">
                        <i class="fa fa-check-circle mr-1"></i> <?php echo xlt('Reactivate Subscription'); ?>
                    </button>
                </form>
            </div>
            <?php endif; ?>

            <!-- Extend Date Action -->
            <div class="col-md-4">
                <form method="post" class="form-inline d-flex">
                    <input type="hidden" name="csrf_token_form" value="<?php echo attr($csrfToken); ?>">
                    <input type="hidden" name="sub_id" value="<?php echo attr($sub['id'] ?? 0); ?>">
                    <input type="hidden" name="form_action" value="extend">
                    <input type="date" name="new_date" class="form-control mr-2 flex-grow-1" required value="<?php echo attr(!empty($sub['current_period_end']) ? date('Y-m-d', strtotime('+30 days', strtotime($sub['current_period_end']))) : date('Y-m-d', strtotime('+30 days'))); ?>">
                    <button type="submit" class="btn btn-outline-primary">
                        <i class="fa fa-calendar-plus-o mr-1"></i> <?php echo xlt('Extend'); ?>
                    </button>
                </form>
            </div>
        </div>
    </div>
    <?php else: ?>
    <div class="alert alert-info">
        <i class="fa fa-info-circle mr-1"></i> <?php echo xlt('Regular users can view subscription status. Only users with the Site Administrator permission can renew or extend subscriptions.'); ?>
    </div>
    <?php endif; ?>

    <!-- Subscription History Audit Log -->
    <div class="card-box">
        <h5 class="mb-3 font-weight-bold"><i class="fa fa-history mr-2 text-secondary"></i> <?php echo xlt('Subscription Audit History'); ?></h5>
        <?php if (!empty($history)): ?>
            <div class="table-responsive">
                <table class="table table-hover table-striped">
                    <thead class="thead-light">
                        <tr>
                            <th><?php echo xlt('Date & Time'); ?></th>
                            <th><?php echo xlt('Action'); ?></th>
                            <th><?php echo xlt('Status Change'); ?></th>
                            <th><?php echo xlt('New Expiry'); ?></th>
                            <th><?php echo xlt('Performed By'); ?></th>
                            <th><?php echo xlt('Notes'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($history as $h): ?>
                        <tr>
                            <td><?php echo htmlspecialchars(date('M d, Y H:i:s', strtotime($h['created_at']))); ?></td>
                            <td><span class="badge badge-secondary"><?php echo htmlspecialchars($h['action']); ?></span></td>
                            <td>
                                <?php if (!empty($h['old_status'])): ?>
                                    <span class="text-muted"><?php echo htmlspecialchars($h['old_status']); ?></span> &rarr;
                                <?php endif; ?>
                                <strong><?php echo htmlspecialchars($h['new_status'] ?? '-'); ?></strong>
                            </td>
                            <td><?php echo htmlspecialchars(!empty($h['new_period_end']) ? date('M d, Y', strtotime($h['new_period_end'])) : '-'); ?></td>
                            <td><?php echo htmlspecialchars($h['performed_by'] ?? 'System'); ?></td>
                            <td><?php echo htmlspecialchars($h['notes'] ?? ''); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="text-muted mb-0"><?php echo xlt('No previous subscription events recorded yet.'); ?></p>
        <?php endif; ?>
    </div>

</div>
</body>
</html>
