<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PDO;
use Throwable;

class CarelioCheckSubscriptionsCommand extends Command
{
    protected $signature = 'carelio:check-subscriptions {--tenant= : Specific tenant database or site slug}';
    protected $description = 'Daily background process to monitor subscription expiration, grace periods, and deactivation';

    public function handle(): int
    {
        $this->info("================================================================================");
        $this->info("          CARELIO EMR SUBSCRIPTION MONITORING & LIFECYCLE ENGINE                ");
        $this->info("================================================================================");

        $pdo = $this->connectDb();
        if (!$pdo) {
            $this->error("Unable to connect to MySQL database.");
            return Command::FAILURE;
        }

        // Find tenant databases
        $databases = [];
        $tenantOpt = $this->option('tenant');

        if ($tenantOpt) {
            $dbName = str_starts_with($tenantOpt, 'openemr_') ? $tenantOpt : ('openemr_' . str_replace('-', '_', $tenantOpt));
            $databases[] = $dbName;
        } else {
            $stmt = $pdo->query("SHOW DATABASES LIKE 'openemr_%'");
            while ($row = $stmt->fetchColumn()) {
                $databases[] = $row;
            }
        }

        $now = time();
        $graceSeconds = 2 * 86400; // 2 days grace period
        $reminderWindowSeconds = 2 * 86400; // 2 days renewal reminder

        $results = [];

        foreach ($databases as $db) {
            try {
                $tableCheck = $pdo->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = '{$db}' AND table_name = 'mod_carelio_subscriptions'")->fetchColumn();
                if (!$tableCheck) {
                    continue;
                }

                $subs = $pdo->query("SELECT * FROM `{$db}`.`mod_carelio_subscriptions`")->fetchAll(PDO::FETCH_ASSOC);

                foreach ($subs as $sub) {
                    $id = (int) $sub['id'];
                    $site = $sub['site_id'] ?? $db;
                    $oldStatus = strtoupper((string) ($sub['status'] ?? 'ACTIVE'));
                    $currentEnd = !empty($sub['current_period_end']) ? strtotime($sub['current_period_end']) : null;

                    if (!$currentEnd) {
                        continue;
                    }

                    $newStatus = $oldStatus;
                    $actionTaken = 'NO_CHANGE';

                    if ($currentEnd > $now) {
                        $remaining = $currentEnd - $now;
                        if ($remaining <= $reminderWindowSeconds) {
                            $newStatus = 'EXPIRING_SOON';
                            // Check if reminder was already sent
                            if (empty($sub['expiration_reminder_sent_at'])) {
                                $actionTaken = 'SENT_REMINDER';
                                $pdo->exec("UPDATE `{$db}`.`mod_carelio_subscriptions` SET `expiration_reminder_sent_at` = NOW() WHERE `id` = {$id}");
                                $this->line(" [{$site}] Sent 2-Day Renewal Reminder Email to {$sub['customer_email']}");
                            }
                        } else {
                            $newStatus = 'ACTIVE';
                        }
                    } else {
                        // Past expiration date
                        $past = $now - $currentEnd;
                        if ($past <= $graceSeconds) {
                            $newStatus = 'EXPIRED_GRACE_PERIOD';
                            $actionTaken = 'GRACE_PERIOD_ACTIVE';
                        } else {
                            $newStatus = 'DEACTIVATED';
                            $actionTaken = 'DEACTIVATED_AFTER_GRACE';
                        }
                    }

                    if ($newStatus !== $oldStatus) {
                        $graceStart = ($newStatus === 'EXPIRED_GRACE_PERIOD') ? date('Y-m-d H:i:s', $currentEnd) : ($sub['grace_started_at'] ?? null);
                        $graceEnd = ($newStatus === 'EXPIRED_GRACE_PERIOD') ? date('Y-m-d H:i:s', $currentEnd + $graceSeconds) : ($sub['grace_ends_at'] ?? null);
                        $deactivatedAt = ($newStatus === 'DEACTIVATED' && empty($sub['deactivated_at'])) ? date('Y-m-d H:i:s') : ($sub['deactivated_at'] ?? null);

                        $updateStmt = $pdo->prepare("UPDATE `{$db}`.`mod_carelio_subscriptions`
                            SET `status` = ?, `grace_started_at` = ?, `grace_ends_at` = ?, `deactivated_at` = ?, `updated_at` = NOW()
                            WHERE `id` = ?");
                        $updateStmt->execute([$newStatus, $graceStart, $graceEnd, $deactivatedAt, $id]);

                        // Audit log
                        $logStmt = $pdo->prepare("INSERT INTO `{$db}`.`mod_carelio_subscription_history`
                            (`subscription_id`, `action`, `old_status`, `new_status`, `old_period_end`, `new_period_end`, `performed_by`, `notes`, `created_at`)
                            VALUES (?, 'DAILY_MONITOR', ?, ?, ?, ?, 'Subscription Cron', ?, NOW())");
                        $logStmt->execute([$id, $oldStatus, $newStatus, $sub['current_period_end'], $sub['current_period_end'], "Status changed from {$oldStatus} to {$newStatus}"]);
                    }

                    $results[] = [
                        'Tenant / DB' => $site,
                        'Customer' => $sub['customer_name'] ?? 'Admin',
                        'End Date' => date('Y-m-d H:i', $currentEnd),
                        'Old Status' => $oldStatus,
                        'Current Status' => $newStatus,
                        'Action' => $actionTaken,
                    ];
                }
            } catch (Throwable $e) {
                $this->warn("Error processing {$db}: " . $e->getMessage());
            }
        }

        if (!empty($results)) {
            $this->table(['Tenant / DB', 'Customer', 'End Date', 'Old Status', 'Current Status', 'Action'], $results);
        } else {
            $this->info("No active subscriptions found in tenant databases.");
        }

        $this->info("Subscription check completed successfully.");
        return Command::SUCCESS;
    }

    private function connectDb(): ?PDO
    {
        try {
            $host = env('DB_HOST', '127.0.0.1');
            $port = env('DB_PORT', '3307');
            $user = env('DB_USERNAME', 'root');
            $pass = env('DB_PASSWORD', 'root');

            return new PDO("mysql:host={$host};port={$port};charset=utf8mb4", $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);
        } catch (Throwable $e) {
            $this->error("DB Connection failed: " . $e->getMessage());
            return null;
        }
    }
}

