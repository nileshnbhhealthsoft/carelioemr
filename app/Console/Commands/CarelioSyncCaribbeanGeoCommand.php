<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use Illuminate\Console\Command;
use PDO;
use Throwable;

require_once base_path('oemr/interface/modules/custom_modules/site_admin_config/src/Installer/CaribbeanDemographicsLoader.php');
use OpenEMR\Modules\SiteAdmin\Installer\CaribbeanDemographicsLoader;

class CarelioSyncCaribbeanGeoCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'carelio:sync-caribbean-geo {--tenant= : Specific tenant database name or slug}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import and synchronize the audited Caribbean Geographic Demographics dataset across tenant databases';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $tenantFilter = $this->option('tenant');

        $this->info("================================================================================");
        $this->info("      CARELIO EMR CARIBBEAN GEOGRAPHIC DEMOGRAPHICS SYNC ENGINE                 ");
        $this->info("================================================================================");

        $driver = config('database.default', 'mysql');
        $host = config("database.connections.{$driver}.host", '127.0.0.1');
        $port = config("database.connections.{$driver}.port", '3306');
        $user = config("database.connections.{$driver}.username", 'root');
        $pass = config("database.connections.{$driver}.password", '');

        // If specific tenant or subscriptions
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
            if ($tenantFilter) {
                // Try connecting directly to db name
                $this->line("Targeting direct database: [{$tenantFilter}]");
                $this->processDatabase($host, $port, $tenantFilter, $user, $pass);
                return Command::SUCCESS;
            }
            $this->warn("No provisioned tenant subscriptions found.");
            return Command::SUCCESS;
        }

        $this->line("Discovered {$subscriptions->count()} tenant database(s) to process.\n");

        $success = 0;
        $failed = 0;

        foreach ($subscriptions as $sub) {
            $db = $sub->openemr_database;
            $slug = $sub->tenant_slug;
            $this->line("Processing tenant #{$sub->id} [{$slug}] -> DB [{$db}]...");

            try {
                $this->processDatabase($host, $port, $db, $user, $pass);
                $success++;
            } catch (Throwable $e) {
                $this->error("  -> Failed on [{$db}]: " . $e->getMessage());
                $failed++;
            }
        }

        $this->info("\nCompleted: {$success} successful, {$failed} failed.");
        return $failed === 0 ? Command::SUCCESS : Command::FAILURE;
    }

    protected function processDatabase(string $host, string|int $port, string $db, string $user, string $pass): void
    {
        $pdo = new PDO("mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4", $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        $sqlPath = base_path('oemr/interface/modules/custom_modules/site_admin_config/table.sql');
        $res = CaribbeanDemographicsLoader::sync($pdo, $sqlPath);

        $this->info("  -> Done: Countries: {$res['list_options']['countries']}, States: {$res['list_options']['states']}, Communities: {$res['list_options']['counties']}");
    }
}
