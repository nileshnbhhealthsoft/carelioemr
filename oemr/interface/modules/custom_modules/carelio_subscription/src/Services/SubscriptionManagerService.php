<?php

namespace OpenEMR\Modules\CarelioSubscription\Services;

use OpenEMR\Common\Session\SessionWrapperFactory;
use OpenEMR\Core\OEGlobalsBag;

class SubscriptionManagerService
{
    public const STATUS_ACTIVE = 'ACTIVE';
    public const STATUS_EXPIRING_SOON = 'EXPIRING_SOON';
    public const STATUS_EXPIRED_GRACE = 'EXPIRED_GRACE_PERIOD';
    public const STATUS_DEACTIVATED = 'DEACTIVATED';

    /**
     * Get or initialize subscription for the current tenant site
     */
    public static function getCurrentSubscription(): array
    {
        if (!function_exists('sqlQuery')) {
            return [];
        }

        $siteId = self::resolveCurrentSiteId();

        $sub = sqlQuery("SELECT * FROM `mod_carelio_subscriptions` WHERE `site_id` = ? OR `tenant_slug` = ? ORDER BY `id` DESC LIMIT 1", [$siteId, $siteId]);

        if (empty($sub)) {
            // Auto-initialize standard active subscription for this tenant if not yet seeded
            self::initializeTenantSubscription($siteId);
            $sub = sqlQuery("SELECT * FROM `mod_carelio_subscriptions` WHERE `site_id` = ? OR `tenant_slug` = ? ORDER BY `id` DESC LIMIT 1", [$siteId, $siteId]);
        }

        if (!empty($sub)) {
            // Synchronize status based on dates
            $sub = self::syncStatus($sub);
        }

        return is_array($sub) ? $sub : [];
    }

    /**
     * Initialize standard initial subscription record
     */
    public static function initializeTenantSubscription(string $siteId): void
    {
        if (!function_exists('sqlStatement')) {
            return;
        }

        $now = date('Y-m-d H:i:s');
        $periodStart = $now;
        $periodEnd = date('Y-m-d H:i:s', strtotime('+30 days'));

        sqlStatement(
            "INSERT INTO `mod_carelio_subscriptions`
                (`external_subscription_id`, `site_id`, `site_name`, `tenant_slug`, `customer_name`, `plan_name`, `plan_amount`, `currency`, `billing_cycle`, `status`, `current_period_start`, `current_period_end`, `created_at`, `updated_at`)
             VALUES (?, ?, ?, ?, ?, 'Starter Plan', 80.00, 'USD', 'monthly', 'ACTIVE', ?, ?, NOW(), NOW())
             ON DUPLICATE KEY UPDATE `updated_at` = NOW()",
            ['sub_' . preg_replace('/[^a-zA-Z0-9]/', '', $siteId), $siteId, ucwords(str_replace(['site-', '-'], ['', ' '], $siteId)), $siteId, 'Site Administrator', $periodStart, $periodEnd]
        );

        self::logHistory((int) sqlInsertId(), 'INITIALIZE', null, self::STATUS_ACTIVE, null, $periodEnd, 'System', 'Initial subscription provisioned.');
    }

    /**
     * Renew subscription (Extends by $months months, resets to ACTIVE)
     */
    public static function renewSubscription(int $subId, string $performedBy, int $months = 1, string $notes = ''): bool
    {
        if ($subId <= 0 || !function_exists('sqlQuery')) {
            return false;
        }

        $sub = sqlQuery("SELECT * FROM `mod_carelio_subscriptions` WHERE `id` = ? LIMIT 1", [$subId]);
        if (empty($sub)) {
            return false;
        }

        $oldStatus = (string) ($sub['status'] ?? self::STATUS_ACTIVE);
        $oldEnd = (string) ($sub['current_period_end'] ?? date('Y-m-d H:i:s'));

        // If currently expired/deactivated, renew starting from today, else extend from current expiration
        $baseTimestamp = strtotime($oldEnd) > time() ? strtotime($oldEnd) : time();
        $newEnd = date('Y-m-d H:i:s', strtotime("+{$months} month", $baseTimestamp));

        sqlStatement(
            "UPDATE `mod_carelio_subscriptions`
             SET `status` = ?,
                 `current_period_end` = ?,
                 `grace_started_at` = NULL,
                 `grace_ends_at` = NULL,
                 `deactivated_at` = NULL,
                 `renewed_at` = NOW(),
                 `expiration_reminder_sent_at` = NULL,
                 `updated_at` = NOW()
             WHERE `id` = ?",
            [self::STATUS_ACTIVE, $newEnd, $subId]
        );

        self::logHistory($subId, 'RENEW', $oldStatus, self::STATUS_ACTIVE, $oldEnd, $newEnd, $performedBy, $notes ?: "Subscription renewed for {$months} month(s).");
        return true;
    }

    /**
     * Extend subscription expiration date directly
     */
    public static function extendExpiration(int $subId, string $performedBy, string $newEndDate, string $notes = ''): bool
    {
        if ($subId <= 0 || !function_exists('sqlQuery')) {
            return false;
        }

        $sub = sqlQuery("SELECT * FROM `mod_carelio_subscriptions` WHERE `id` = ? LIMIT 1", [$subId]);
        if (empty($sub)) {
            return false;
        }

        $oldStatus = (string) ($sub['status'] ?? self::STATUS_ACTIVE);
        $oldEnd = (string) ($sub['current_period_end'] ?? date('Y-m-d H:i:s'));

        $newTimestamp = strtotime($newEndDate);
        if (!$newTimestamp) {
            return false;
        }

        $formattedNewEnd = date('Y-m-d 23:59:59', $newTimestamp);
        $computedStatus = self::calculateStatusFromDate($formattedNewEnd);

        sqlStatement(
            "UPDATE `mod_carelio_subscriptions`
             SET `status` = ?,
                 `current_period_end` = ?,
                 `grace_started_at` = NULL,
                 `grace_ends_at` = NULL,
                 `deactivated_at` = NULL,
                 `expiration_reminder_sent_at` = NULL,
                 `updated_at` = NOW()
             WHERE `id` = ?",
            [$computedStatus, $formattedNewEnd, $subId]
        );

        self::logHistory($subId, 'EXTEND', $oldStatus, $computedStatus, $oldEnd, $formattedNewEnd, $performedBy, $notes ?: "Expiration extended to {$formattedNewEnd}.");
        return true;
    }

    /**
     * Reactivate deactivated subscription
     */
    public static function reactivateSubscription(int $subId, string $performedBy, string $notes = ''): bool
    {
        return self::renewSubscription($subId, $performedBy, 1, $notes ?: 'Subscription reactivated by Site Administrator.');
    }

    /**
     * Simulate any status for testing (ACTIVE, EXPIRING_SOON, EXPIRED_GRACE_PERIOD, DEACTIVATED)
     */
    public static function simulateStatus(int $subId, string $targetStatus, string $performedBy): bool
    {
        if ($subId <= 0 || !function_exists('sqlQuery')) {
            return false;
        }

        $sub = sqlQuery("SELECT * FROM `mod_carelio_subscriptions` WHERE `id` = ? LIMIT 1", [$subId]);
        if (empty($sub)) {
            return false;
        }

        $oldStatus = (string) ($sub['status'] ?? self::STATUS_ACTIVE);
        $oldEnd = (string) ($sub['current_period_end'] ?? date('Y-m-d H:i:s'));

        $now = time();
        $newEnd = $oldEnd;
        $graceStart = null;
        $graceEnd = null;
        $deactivatedAt = null;

        switch ($targetStatus) {
            case self::STATUS_ACTIVE:
                $newEnd = date('Y-m-d 23:59:59', strtotime('+30 days', $now));
                break;

            case self::STATUS_EXPIRING_SOON:
                // End date is within 2 days (e.g. tomorrow)
                $newEnd = date('Y-m-d H:i:s', strtotime('+1 day', $now));
                break;

            case self::STATUS_EXPIRED_GRACE:
                // Expired 1 day ago, grace period ends tomorrow (2 days grace)
                $newEnd = date('Y-m-d H:i:s', strtotime('-1 day', $now));
                $graceStart = $newEnd;
                $graceEnd = date('Y-m-d H:i:s', strtotime('+1 day', $now));
                break;

            case self::STATUS_DEACTIVATED:
                // Expired 3 days ago, grace period passed
                $newEnd = date('Y-m-d H:i:s', strtotime('-3 days', $now));
                $graceStart = $newEnd;
                $graceEnd = date('Y-m-d H:i:s', strtotime('-1 day', $now));
                $deactivatedAt = date('Y-m-d H:i:s', strtotime('-1 day', $now));
                break;

            default:
                return false;
        }

        sqlStatement(
            "UPDATE `mod_carelio_subscriptions`
             SET `status` = ?,
                 `current_period_end` = ?,
                 `grace_started_at` = ?,
                 `grace_ends_at` = ?,
                 `deactivated_at` = ?,
                 `updated_at` = NOW()
             WHERE `id` = ?",
            [$targetStatus, $newEnd, $graceStart, $graceEnd, $deactivatedAt, $subId]
        );

        self::logHistory($subId, 'SIMULATE', $oldStatus, $targetStatus, $oldEnd, $newEnd, $performedBy, "Status simulated to {$targetStatus} for testing.");
        return true;
    }

    /**
     * Compute and sync status based on current expiration timestamp
     */
    public static function syncStatus(array $sub): array
    {
        $currentEnd = !empty($sub['current_period_end']) ? strtotime($sub['current_period_end']) : null;
        if (!$currentEnd) {
            return $sub;
        }

        $now = time();
        $storedStatus = strtoupper((string) ($sub['status'] ?? self::STATUS_ACTIVE));
        $newStatus = $storedStatus;

        // Grace period is 2 days (172800 seconds)
        $graceSeconds = 2 * 86400;

        if ($currentEnd > $now) {
            // Future expiration
            $secondsRemaining = $currentEnd - $now;
            if ($secondsRemaining <= (2 * 86400)) {
                $newStatus = self::STATUS_EXPIRING_SOON;
            } else {
                $newStatus = self::STATUS_ACTIVE;
            }
        } else {
            // Already expired
            $secondsPast = $now - $currentEnd;
            if ($secondsPast <= $graceSeconds) {
                // In 2-day Grace Period
                $newStatus = self::STATUS_EXPIRED_GRACE;
            } else {
                // Grace period passed -> DEACTIVATED
                $newStatus = self::STATUS_DEACTIVATED;
            }
        }

        if ($newStatus !== $storedStatus) {
            $graceStart = ($newStatus === self::STATUS_EXPIRED_GRACE) ? date('Y-m-d H:i:s', $currentEnd) : ($sub['grace_started_at'] ?? null);
            $graceEnd = ($newStatus === self::STATUS_EXPIRED_GRACE) ? date('Y-m-d H:i:s', $currentEnd + $graceSeconds) : ($sub['grace_ends_at'] ?? null);
            $deactivatedAt = ($newStatus === self::STATUS_DEACTIVATED && empty($sub['deactivated_at'])) ? date('Y-m-d H:i:s') : ($sub['deactivated_at'] ?? null);

            sqlStatement(
                "UPDATE `mod_carelio_subscriptions`
                 SET `status` = ?,
                     `grace_started_at` = ?,
                     `grace_ends_at` = ?,
                     `deactivated_at` = ?,
                     `updated_at` = NOW()
                 WHERE `id` = ?",
                [$newStatus, $graceStart, $graceEnd, $deactivatedAt, $sub['id']]
            );

            self::logHistory((int) $sub['id'], 'AUTO_TRANSITION', $storedStatus, $newStatus, $sub['current_period_end'], $sub['current_period_end'], 'System Monitor', "Automated status update to {$newStatus}.");

            $sub['status'] = $newStatus;
            $sub['grace_started_at'] = $graceStart;
            $sub['grace_ends_at'] = $graceEnd;
            $sub['deactivated_at'] = $deactivatedAt;
        }

        return $sub;
    }

    /**
     * Calculate status string from date
     */
    private static function calculateStatusFromDate(string $dateString): string
    {
        $timestamp = strtotime($dateString);
        if (!$timestamp) {
            return self::STATUS_ACTIVE;
        }

        $now = time();
        if ($timestamp > $now) {
            return (($timestamp - $now) <= 2 * 86400) ? self::STATUS_EXPIRING_SOON : self::STATUS_ACTIVE;
        }

        $secondsPast = $now - $timestamp;
        return ($secondsPast <= 2 * 86400) ? self::STATUS_EXPIRED_GRACE : self::STATUS_DEACTIVATED;
    }

    /**
     * Get audit history records
     */
    public static function getHistory(int $subId, int $limit = 20): array
    {
        if ($subId <= 0 || !function_exists('sqlStatement')) {
            return [];
        }

        $res = sqlStatement("SELECT * FROM `mod_carelio_subscription_history` WHERE `subscription_id` = ? ORDER BY `id` DESC LIMIT ?", [$subId, $limit]);
        $rows = [];
        while ($row = sqlFetchArray($res)) {
            $rows[] = $row;
        }
        return $rows;
    }

    /**
     * Log history event
     */
    public static function logHistory(
        int $subId,
        string $action,
        ?string $oldStatus,
        ?string $newStatus,
        ?string $oldEnd,
        ?string $newEnd,
        ?string $performedBy = null,
        ?string $notes = null
    ): void {
        if (!function_exists('sqlStatement')) {
            return;
        }

        sqlStatement(
            "INSERT INTO `mod_carelio_subscription_history`
                (`subscription_id`, `action`, `old_status`, `new_status`, `old_period_end`, `new_period_end`, `performed_by`, `notes`, `created_at`)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())",
            [$subId, $action, $oldStatus, $newStatus, $oldEnd, $newEnd, $performedBy, $notes]
        );
    }

    /**
     * Resolve site ID
     */
    public static function resolveCurrentSiteId(): string
    {
        if (class_exists(SessionWrapperFactory::class)) {
            try {
                $session = SessionWrapperFactory::getInstance()->getActiveSession();
                $id = $session->get('site_id');
                if (!empty($id)) {
                    return (string) $id;
                }
            } catch (\Throwable) {
            }
        }

        if (!empty($_SESSION['site_id'])) {
            return (string) $_SESSION['site_id'];
        }

        if (!empty($_GET['site'])) {
            return (string) $_GET['site'];
        }

        if (class_exists(OEGlobalsBag::class)) {
            $siteDir = OEGlobalsBag::getInstance()->get('OE_SITE_DIR');
            if (!empty($siteDir)) {
                return basename((string) $siteDir);
            }
        }

        return 'default';
    }
}

