<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use Database\Seeders\OpenemrDemographicsLayoutSeeder;
use Database\Seeders\OpenemrLocationListsSeeder;
use Illuminate\Console\Command;
use PDO;
use Throwable;

class OpenEmrSeedLocationsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'openemr:seed-locations {--tenant= : Specific tenant database name or slug to seed}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed global location lists (Country, State, County) and configure dynamic cascading dropdowns on patient demographics across tenant databases';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $tenantFilter = $this->option('tenant');

        $this->info("================================================================================");
        $this->info("      OPENEMR LOCATION LISTS & CASCADING DROPDOWNS SEEDING ENGINE              ");
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

        $locationSeeder = app(OpenemrLocationListsSeeder::class);
        $demographicsSeeder = app(OpenemrDemographicsLayoutSeeder::class);

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

                // Run location lists seeder (seeds list_options and configures layout_options with cascading script)
                $locSummary = $locationSeeder->runOnPdo($pdo);

                // Run demographics layout seeder to hide unused fields
                $hiddenCount = $demographicsSeeder->runOnPdo($pdo);

                $this->info("  -> Done: Countries: {$locSummary['country']}, States: {$locSummary['state']}, Counties: {$locSummary['county']}, Unused Fields Hidden: {$hiddenCount}");
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
        $this->info("Seeding complete: {$successCount} succeeded, {$failCount} failed.");
        $this->info("================================================================================");

        return $failCount === 0 ? Command::SUCCESS : Command::FAILURE;
    }
}
