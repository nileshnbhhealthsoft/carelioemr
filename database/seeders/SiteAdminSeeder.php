<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use OpenEMR\Modules\SiteAdmin\Installer\SiteAdminInstaller;
use PDO;

class SiteAdminSeeder extends Seeder
{
    /**
     * Run the database seeds on the default, specified connection, or across all active tenants.
     */
    public function run(?string $connection = null): void
    {
        if ($connection) {
            $pdo = DB::connection($connection)->getPdo();
            $this->runOnPdo($pdo);
            return;
        }

        $currentDb = DB::connection()->getDatabaseName();
        if (str_starts_with($currentDb, 'openemr_site_')) {
            $this->runOnPdo(DB::connection()->getPdo());
            return;
        }

        // Master DB: iterate over all completed subscriptions with an OpenEMR database
        $subscriptions = \App\Models\Subscription::where('provision_status', 'completed')
            ->whereNotNull('openemr_database')
            ->get();

        if ($subscriptions->isEmpty()) {
            $this->command?->warn("No completed OpenEMR tenant subscriptions found to seed.");
            return;
        }

        $host = config('database.connections.mysql.host', '127.0.0.1');
        $port = config('database.connections.mysql.port', '3306');
        $user = config('database.connections.mysql.username', 'root');
        $pass = config('database.connections.mysql.password', '');

        foreach ($subscriptions as $sub) {
            $dbName = $sub->openemr_database;
            $this->command?->info("Seeding OpenEMR tenant: {$sub->tenant_slug} ({$dbName})...");
            try {
                $tenantPdo = new PDO("mysql:host={$host};port={$port};dbname={$dbName};charset=utf8mb4", $user, $pass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                ]);
                $siteDir = base_path('oemr/sites/' . $sub->tenant_slug);
                $this->runOnPdo($tenantPdo, null, $sub->doctor_name, $siteDir);
                $this->command?->info("Successfully seeded tenant: {$sub->tenant_slug}");
            } catch (\Throwable $e) {
                $this->command?->error("Failed seeding tenant {$sub->tenant_slug}: " . $e->getMessage());
            }
        }
    }

    /**
     * Execute native branding and invoke Site Administrator module installer on target PDO instance.
     */
    public function runOnPdo(PDO $pdo, ?string $doctorUsername = null, ?string $doctorFullName = null, ?string $siteDir = null): void
    {
        // -------------------------------------------------------------
        // 1. Delegate Site Administrator Setup, table.sql & ACL to Custom Module Installer
        // -------------------------------------------------------------
        $this->ensureModuleClassesLoaded();

        // Idempotently execute table.sql and configure Site Administrator group/ACLs via single entry point
        SiteAdminInstaller::install($pdo, $siteDir);

        // -------------------------------------------------------------
        // 3. Map Target Doctor User(s) via Custom Module
        // -------------------------------------------------------------
        $doctorsToMap = [];
        if (!empty($doctorUsername)) {
            $doctorsToMap[] = [
                'username' => $doctorUsername,
                'name' => $doctorFullName ?: $doctorUsername
            ];
        } else {
            // Map all non-admin authorized providers in this tenant
            $stmtDocs = $pdo->query("SELECT username, CONCAT(COALESCE(fname,''), ' ', COALESCE(lname,'')) AS full_name FROM users WHERE username != 'admin' AND authorized = 1");
            while ($row = $stmtDocs->fetch(PDO::FETCH_ASSOC)) {
                $doctorsToMap[] = [
                    'username' => $row['username'],
                    'name' => trim($row['full_name']) ?: $row['username']
                ];
            }
        }

        foreach ($doctorsToMap as $doc) {
            if (strcasecmp($doc['username'], 'admin') === 0) {
                continue; // Safety guard: never map root admin
            }
            SiteAdminInstaller::assignUser($doc['username'], $doc['name'], $pdo);
        }
    }

    /**
     * Ensure OpenEMR autoloader and module classes are loaded in Laravel context
     */
    protected function ensureModuleClassesLoaded(): void
    {
        if (!class_exists(SiteAdminInstaller::class)) {
            $oemrAutoload = base_path('oemr/vendor/autoload.php');
            if (file_exists($oemrAutoload)) {
                require_once $oemrAutoload;
            }

            $moduleBootstrap = base_path('oemr/interface/modules/custom_modules/site_admin_config/openemr.bootstrap.php');
            if (file_exists($moduleBootstrap)) {
                require_once $moduleBootstrap;
            }

            $installerClass = base_path('oemr/interface/modules/custom_modules/site_admin_config/src/Installer/SiteAdminInstaller.php');
            if (file_exists($installerClass)) {
                require_once $installerClass;
            }
        }
    }
}

// Alias for convenience if invoked as SiteAdminConfigSeeder
if (!class_exists(SiteAdminConfigSeeder::class, false)) {
    class_alias(SiteAdminSeeder::class, 'Database\Seeders\SiteAdminConfigSeeder');
}

