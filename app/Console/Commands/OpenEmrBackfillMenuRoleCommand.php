<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use Illuminate\Console\Command;
use PDO;
use Throwable;

class OpenEmrBackfillMenuRoleCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'openemr:backfill-menu-role 
                            {--tenant= : Specific tenant database name, slug, or subscription ID}
                            {--all : Explicitly confirm batch backfill across ALL provisioned tenants}
                            {--dry-run : Simulate execution and inspect targets without writing changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Safely install Site Administrator custom module and assign roles on targeted tenant(s)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $tenantFilter = $this->option('tenant');
        $all = (bool) $this->option('all');
        $dryRun = (bool) $this->option('dry-run');

        $this->info("================================================================================");
        $this->info("      OPENEMR TENANT SITE ADMINISTRATOR MODULE BACKFILL & SETUP                 ");
        $this->info("================================================================================");

        // Safety Guard: Require either --tenant or explicit --all
        if (empty($tenantFilter) && !$all) {
            $this->error("SAFETY GUARD TRIGGERED: Neither --tenant nor --all was specified.");
            $this->warn("To target a single demo or specific tenant, run:");
            $this->line("  php artisan openemr:backfill-menu-role --tenant=<id|slug|dbname> [--dry-run]");
            $this->warn("To explicitly target all tenants in production, you must supply --all:");
            $this->line("  php artisan openemr:backfill-menu-role --all [--dry-run]");
            return Command::FAILURE;
        }

        if ($dryRun) {
            $this->warn(">>> DRY-RUN MODE ENABLED: No database or file modifications will be committed. <<<\n");
        }

        $query = Subscription::whereNotNull('openemr_database')
            ->where('openemr_database', '!=', '');

        if ($tenantFilter) {
            $query->where(function ($q) use ($tenantFilter) {
                $q->where('id', $tenantFilter)
                  ->orWhere('openemr_database', $tenantFilter)
                  ->orWhere('tenant_slug', $tenantFilter);
            });
        }

        $subscriptions = $query->orderBy('id', 'asc')->get();

        if ($subscriptions->isEmpty()) {
            $this->warn("No matching provisioned tenant databases found for criteria: " . ($tenantFilter ?: 'ALL'));
            return Command::SUCCESS;
        }

        $this->info("Target Tenant(s) identified (" . $subscriptions->count() . "):");
        foreach ($subscriptions as $sub) {
            $this->line("  • #{$sub->id} | Slug: [{$sub->tenant_slug}] | DB: [{$sub->openemr_database}] | Doctor: [{$sub->doctor_name}]");
        }
        $this->newLine();

        $driver = config('database.default', 'mysql');
        $host = config("database.connections.{$driver}.host", '127.0.0.1');
        $port = config("database.connections.{$driver}.port", '3306');
        $user = config("database.connections.{$driver}.username", 'root');
        $pass = config("database.connections.{$driver}.password", '');

        $successCount = 0;
        $failCount = 0;
        $skippedCount = 0;

        foreach ($subscriptions as $subscription) {
            $dbName = $subscription->openemr_database;
            $slug = $subscription->tenant_slug;

            $this->line("Processing tenant #{$subscription->id} [{$slug}] -> DB [{$dbName}]...");

            try {
                $pdo = new PDO("mysql:host={$host};port={$port};dbname={$dbName};charset=utf8mb4", $user, $pass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]);

                // Dry-run simulation
                if ($dryRun) {
                    $userList = $pdo->query("SELECT username, main_menu_role FROM users ORDER BY id ASC")->fetchAll();
                    $rolesSummary = array_map(fn($u) => "{$u['username']}: {$u['main_menu_role']}", $userList);
                    $this->info("  [DRY-RUN SIMULATED] Would backup DB [{$dbName}], install test custom module, and assign Site Admin role. Current users: (" . implode(', ', $rolesSummary) . ")");
                    $successCount++;
                    continue;
                }

                // 1. Automatic Database Backup before writes
                $backupPath = $this->createTenantBackup($pdo, $dbName);
                $this->info("  -> Pre-write backup successfully created: {$backupPath}");

                // 2. Set tenant context for OpenEMR
                $siteDir = base_path("oemr/sites/{$slug}");
                if (!is_dir($siteDir)) {
                    $candidate = base_path("oemr/sites/site-{$slug}");
                    if (is_dir($candidate)) {
                        $siteDir = $candidate;
                    }
                }
                if (class_exists(\OpenEMR\Core\OEGlobalsBag::class)) {
                    \OpenEMR\Core\OEGlobalsBag::getInstance()->set('OE_SITE_DIR', $siteDir);
                }

                // 3. Delegate to native branding & test custom module installer
                app(\Database\Seeders\OpenemrTenantAclAndBrandingSeeder::class)->runOnPdo($pdo);

                // 4. Fetch current user roles for reporting
                $userList = $pdo->query("SELECT username, main_menu_role FROM users ORDER BY id ASC")->fetchAll();
                $rolesSummary = array_map(fn($u) => "{$u['username']}: {$u['main_menu_role']}", $userList);

                $this->info("  -> Done: Native branding & Site Administrator custom module configured. (" . implode(', ', $rolesSummary) . ")");
                $successCount++;
            } catch (Throwable $e) {
                if (str_contains($e->getMessage(), 'Unknown database')) {
                    $this->warn("  -> Skipped: Database [{$dbName}] does not exist on MySQL server.");
                    $skippedCount++;
                } else {
                    $this->error("  -> Failed on [{$dbName}]: " . $e->getMessage());
                    $failCount++;
                }
            }
        }

        $this->newLine();
        $this->info("================================================================================");
        $this->info("Backfill complete: {$successCount} succeeded, {$skippedCount} skipped, {$failCount} failed.");
        $this->info("================================================================================");

        return $failCount === 0 ? Command::SUCCESS : Command::FAILURE;
    }

    /**
     * Create an emergency pre-write SQL backup of the target tenant database
     */
    protected function createTenantBackup(PDO $pdo, string $dbName): string
    {
        $backupDir = storage_path('app/backups');
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        $timestamp = date('Ymd_His');
        $backupFile = "{$backupDir}/backup_pre_backfill_{$dbName}_{$timestamp}.sql";

        $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

        $handle = fopen($backupFile, 'w');
        fwrite($handle, "-- Pre-backfill backup of {$dbName} generated at {$timestamp}\nSET FOREIGN_KEY_CHECKS=0;\n");

        foreach ($tables as $table) {
            $create = $pdo->query("SHOW CREATE TABLE `{$table}`")->fetch(PDO::FETCH_ASSOC);
            fwrite($handle, "\nDROP TABLE IF EXISTS `{$table}`;\n" . $create['Create Table'] . ";\n");

            $rows = $pdo->query("SELECT * FROM `{$table}`")->fetchAll(PDO::FETCH_ASSOC);
            if (!empty($rows)) {
                foreach ($rows as $row) {
                    $cols = array_map(fn($c) => "`" . str_replace("`", "``", $c) . "`", array_keys($row));
                    $vals = array_map(function ($v) use ($pdo) {
                        return $v === null ? "NULL" : $pdo->quote($v);
                    }, array_values($row));
                    fwrite($handle, "INSERT INTO `{$table}` (" . implode(",", $cols) . ") VALUES (" . implode(",", $vals) . ");\n");
                }
            }
        }

        fwrite($handle, "SET FOREIGN_KEY_CHECKS=1;\n");
        fclose($handle);

        return $backupFile;
    }
}
