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
    protected $signature = 'openemr:backfill-menu-role {--tenant= : Specific tenant database name or slug}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Assign site_admin menu role to all non-admin tenant users while preserving admin standard menu across tenant databases';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $tenantFilter = $this->option('tenant');

        $this->info("================================================================================");
        $this->info("      OPENEMR TENANT USER MENU ROLE (SITE_ADMIN) BATCH BACKFILL                 ");
        $this->info("================================================================================");

        $query = Subscription::whereNotNull('openemr_database')
            ->where('openemr_database', '!=', '');

        if ($tenantFilter) {
            $query->where(function ($q) use ($tenantFilter) {
                $q->where('openemr_database', $tenantFilter)
                  ->orWhere('tenant_slug', $tenantFilter);
            });
        }

        $subscriptions = $query->orderBy('id', 'asc')->get();

        if ($subscriptions->isEmpty()) {
            $this->warn("No matching provisioned tenant databases found.");
            return Command::SUCCESS;
        }

        $this->line("Discovered {$subscriptions->count()} tenant database(s) to process.\n");

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

                // Apply native branding (globals) and phpGACL Site Administrator access restrictions
                app(\Database\Seeders\OpenemrTenantAclAndBrandingSeeder::class)->runOnPdo($pdo);

                // Fetch current user roles for reporting
                $userList = $pdo->query("SELECT username, main_menu_role FROM users ORDER BY id ASC")->fetchAll();
                $rolesSummary = array_map(fn($u) => "{$u['username']}: {$u['main_menu_role']}", $userList);

                $this->info("  -> Done: Native branding & phpGACL Site Administrator role configured. (" . implode(', ', $rolesSummary) . ")");
                $successCount++;
            } catch (Throwable $e) {
                if (str_contains($e->getMessage(), 'Unknown database')) {
                    $this->warn("  -> Skipped: Database [{$dbName}] does not exist on MySQL server (e.g. rolled-back/failed test).");
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
}

