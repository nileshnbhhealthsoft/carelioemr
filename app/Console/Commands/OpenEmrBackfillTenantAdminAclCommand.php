<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use App\Services\OpenEmr\OpenEmrCanonicalAclSeeder;
use App\Services\OpenEmr\OpenEmrTenantAuditService;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use PDO;
use RuntimeException;
use Throwable;

class OpenEmrBackfillTenantAdminAclCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'openemr:backfill-tenant-admin-acl {--dry-run : Preview changes without writing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backfill existing provisioned tenants to the safe Tenant Administrators ACL';

    /**
     * Execute the console command.
     */
    public function handle(
        OpenEmrCanonicalAclSeeder $aclSeeder,
        OpenEmrTenantAuditService $auditService
    ): int {
        $dryRun = (bool) $this->option('dry-run');

        $this->info("================================================================================");
        $this->info("   CANONICAL OPENEMR 8.3.0 TENANT ADMINISTRATORS ACL BATCH BACKFILL ENGINE   ");
        $this->info("================================================================================");
        if ($dryRun) {
            $this->warn("RUNNING IN DRY-RUN MODE: No databases will be backed up or modified.");
        }

        // 1. Discover all managed tenant subscriptions
        $subscriptions = Subscription::whereNotNull('openemr_database')
            ->orderBy('id', 'asc')
            ->get();

        $totalDiscovered = $subscriptions->count();
        $this->line("Discovered {$totalDiscovered} total tenant subscription(s) in Laravel database.\n");

        $driver = config('database.default', 'mysql');
        $host = config("database.connections.{$driver}.host", '127.0.0.1');
        $port = config("database.connections.{$driver}.port", '3307');
        $user = config("database.connections.{$driver}.username", 'root');
        $pass = config("database.connections.{$driver}.password", 'root');

        $updated = [];
        $skipped = [];
        $failures = [];

        foreach ($subscriptions as $subscription) {
            $subId = (int) $subscription->id;
            $dbName = $subscription->openemr_database;
            $tenantSlug = $subscription->tenant_slug;
            $docName = $subscription->doctor_name;
            $docUsername = Str::slug($docName, '_') ?: ('doctor_' . $subId);

            // Guard 1: Verify database connection and tables
            try {
                $pdo = new PDO("mysql:host={$host};port={$port};dbname={$dbName};charset=utf8mb4", $user, $pass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                ]);
            } catch (Exception $e) {
                $reason = "Database unreachable or does not exist ({$e->getMessage()})";
                $skipped[] = ['id' => $subId, 'slug' => $tenantSlug, 'reason' => $reason];
                $this->line("Subscription #{$subId} ({$tenantSlug}): SKIPPED - {$reason}");
                continue;
            }

            // Guard 2: Verify gacl_aro and users tables exist
            $hasGacl = (bool) $pdo->query("SHOW TABLES LIKE 'gacl_aro'")->fetchColumn();
            $hasUsers = (bool) $pdo->query("SHOW TABLES LIKE 'users'")->fetchColumn();
            if (!$hasGacl || !$hasUsers) {
                $reason = "Missing core tables (gacl_aro / users uninitialized)";
                $skipped[] = ['id' => $subId, 'slug' => $tenantSlug, 'reason' => $reason];
                $this->line("Subscription #{$subId} ({$tenantSlug}): SKIPPED - {$reason}");
                continue;
            }

            // Guard 3: Verify site directory exists
            $openEmrBasePath = rtrim((string) config('oemr.base_path', base_path('oemr')), '/\\');
            $sitePath = $openEmrBasePath . '/sites/' . $tenantSlug;
            if (!File::isDirectory($sitePath)) {
                $reason = "Missing site directory at oemr/sites/{$tenantSlug}";
                $skipped[] = ['id' => $subId, 'slug' => $tenantSlug, 'reason' => $reason];
                $this->line("Subscription #{$subId} ({$tenantSlug}): SKIPPED - {$reason}");
                continue;
            }

            // Guard 4: Check if doctor user is already in Tenant Administrators and not in Administrators/Emergency Login
            $currentDocGroups = $pdo->query("
                SELECT g.name, g.value 
                FROM gacl_groups_aro_map gam 
                JOIN gacl_aro a ON gam.aro_id = a.id 
                JOIN gacl_aro_groups g ON gam.group_id = g.id 
                WHERE a.value = '{$docUsername}'
            ")->fetchAll(PDO::FETCH_ASSOC);

            $groupValues = array_column($currentDocGroups, 'value');
            $isAlreadyTenantAdmin = in_array('tenant_admin', $groupValues, true);
            $hasSuperGroup = in_array('admin', $groupValues, true) || in_array('breakglass', $groupValues, true);

            if ($isAlreadyTenantAdmin && !$hasSuperGroup) {
                $reason = "Already synchronized with Tenant Administrators";
                $skipped[] = ['id' => $subId, 'slug' => $tenantSlug, 'reason' => $reason];
                $this->line("Subscription #{$subId} ({$tenantSlug}): SKIPPED - {$reason}");
                continue;
            }

            if ($dryRun) {
                $updated[] = [
                    'id' => $subId,
                    'slug' => $tenantSlug,
                    'doctor' => $docUsername,
                    'current_groups' => implode(', ', array_column($currentDocGroups, 'name')),
                ];
                $this->info("Subscription #{$subId} ({$tenantSlug}): PLAN TO UPDATE - Move '{$docUsername}' to Tenant Administrators");
                continue;
            }

            // Live Update Flow
            $this->info("--------------------------------------------------------------------------------");
            $this->info("Processing Subscription #{$subId} ({$tenantSlug}) - Database: {$dbName}");

            try {
                // Step A: Create verified backup before modifying DB
                $backupInfo = $this->createVerifiedBackup($subscription);
                $this->line("   [BACKUP] Verified backup created: {$backupInfo['file_name']} (" . number_format($backupInfo['file_size']) . " bytes)");

                // Step B: Apply safe Tenant Administrators mapping
                $aclSeeder->ensureTenantAdminUserAclMapped($dbName, $docUsername, $docName);
                $this->line("   [ACL] Applied ensureTenantAdminUserAclMapped for '{$docUsername}'");

                // Step C: Verify post-migration invariants
                // 1. Customer user has tenant_admin and NOT admin/breakglass
                $postDocGroups = $pdo->query("
                    SELECT g.value 
                    FROM gacl_groups_aro_map gam 
                    JOIN gacl_aro a ON gam.aro_id = a.id 
                    JOIN gacl_aro_groups g ON gam.group_id = g.id 
                    WHERE a.value = '{$docUsername}'
                ")->fetchAll(PDO::FETCH_COLUMN);

                if (!in_array('tenant_admin', $postDocGroups, true) || in_array('admin', $postDocGroups, true) || in_array('breakglass', $postDocGroups, true)) {
                    throw new RuntimeException("Post-migration group verification failed for '{$docUsername}' (groups: " . implode(', ', $postDocGroups) . ")");
                }

                // 2. System admin user still in Administrators
                $adminGroups = $pdo->query("
                    SELECT g.value 
                    FROM gacl_groups_aro_map gam 
                    JOIN gacl_aro a ON gam.aro_id = a.id 
                    JOIN gacl_aro_groups g ON gam.group_id = g.id 
                    WHERE a.value = 'admin'
                ")->fetchAll(PDO::FETCH_COLUMN);

                if (!in_array('admin', $adminGroups, true)) {
                    throw new RuntimeException("CRITICAL: Built-in OEMR admin user was detached from Administrators group");
                }

                // 3. OpenEMR environment ACL check
                require_once $openEmrBasePath . '/vendor/autoload.php';
                \OpenEMR\Core\OEGlobalsBag::getInstance()->set('OE_SITE_DIR', $sitePath);
                \OpenEMR\Core\OEGlobalsBag::getInstance()->set('connection_pooling_off', true);

                $refProp = new \ReflectionProperty(\OpenEMR\Common\Acl\AclMain::class, 'gaclObject');
                $refProp->setAccessible(true);
                $refProp->setValue(null, null);

                if (class_exists(\Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage::class) && class_exists(\OpenEMR\Common\Session\SessionWrapperFactory::class)) {
                    \OpenEMR\Common\Session\SessionWrapperFactory::getInstance()->setActiveSession(
                        new \Symfony\Component\HttpFoundation\Session\Session(
                            new \Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage()
                        )
                    );
                }

                $canUsers = \OpenEMR\Common\Acl\AclMain::aclCheckCore('admin', 'users', $docUsername);
                $isSuper = \OpenEMR\Common\Acl\AclMain::aclCheckCore('admin', 'super', $docUsername);
                $isAcl = \OpenEMR\Common\Acl\AclMain::aclCheckCore('admin', 'acl', $docUsername);

                if (!$canUsers || $isSuper || $isAcl) {
                    throw new RuntimeException("ACL check invariant failed for {$docUsername}: users=" . ($canUsers ? 'true' : 'false') . ", super=" . ($isSuper ? 'true' : 'false') . ", acl=" . ($isAcl ? 'true' : 'false'));
                }

                // 4. Tenant health check
                $audit = $auditService->auditTenant($subscription);
                if (!$audit['version_compatible'] || empty($audit['admin_user_audit']['gacl_mapped'])) {
                    throw new RuntimeException("Tenant health audit failed post-migration");
                }

                $updated[] = [
                    'id' => $subId,
                    'slug' => $tenantSlug,
                    'doctor' => $docUsername,
                    'backup' => $backupInfo['file_name'],
                ];
                $this->info("   [SUCCESS] Updated Subscription #{$subId} ({$tenantSlug}) successfully");

            } catch (Throwable $e) {
                $failures[] = [
                    'id' => $subId,
                    'slug' => $tenantSlug,
                    'error' => $e->getMessage(),
                ];
                $this->error("   [FAILED] Subscription #{$subId} ({$tenantSlug}): " . $e->getMessage());
                Log::error("Tenant ACL backfill failed for #{$subId} ({$tenantSlug}): " . $e->getMessage(), [
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        $this->info("================================================================================");
        $this->info("BATCH BACKFILL EXECUTION COMPLETE");
        $this->line("Discovered: " . $totalDiscovered);
        $this->line("Updated:    " . count($updated));
        $this->line("Skipped:    " . count($skipped));
        $this->line("Failures:   " . count($failures));
        $this->info("================================================================================");

        return count($failures) > 0 ? 1 : 0;
    }

    /**
     * Create verified database backup using mysqldump
     */
    protected function createVerifiedBackup(Subscription $subscription): array
    {
        $backupDir = storage_path('app/openemr_backups');
        if (!File::exists($backupDir)) {
            File::makeDirectory($backupDir, 0755, true);
        }

        $timestamp = date('Ymd_His');
        $dbName = $subscription->openemr_database;
        $tenantSlug = $subscription->tenant_slug;
        $backupFilename = "backup_{$tenantSlug}_sub{$subscription->id}_{$dbName}_{$timestamp}.sql";
        $backupFilePath = $backupDir . '/' . $backupFilename;

        $mysqlDumpExe = $this->getMysqlDumpPath();

        $driver = config('database.default', 'mysql');
        $host = config("database.connections.{$driver}.host", '127.0.0.1');
        $port = config("database.connections.{$driver}.port", '3307');
        $user = config("database.connections.{$driver}.username", 'root');
        $pass = config("database.connections.{$driver}.password", 'root');

        $passArg = $pass !== '' ? "-p{$pass}" : '';
        $cmd = "cmd.exe /c \"\"{$mysqlDumpExe}\" -h {$host} -P {$port} -u {$user} {$passArg} --set-gtid-purged=OFF --single-transaction --quick --routines --triggers {$dbName} > \"{$backupFilePath}\"\"";

        exec($cmd, $output, $exitCode);

        if ($exitCode !== 0 || !File::exists($backupFilePath)) {
            if (File::exists($backupFilePath)) {
                File::delete($backupFilePath);
            }
            throw new RuntimeException("mysqldump failed with exit code {$exitCode} for {$dbName}");
        }

        $fileSize = filesize($backupFilePath);
        if ($fileSize < 50000) {
            File::delete($backupFilePath);
            throw new RuntimeException("Backup file size is suspiciously small ({$fileSize} bytes) for {$dbName}");
        }

        $sha256 = hash_file('sha256', $backupFilePath);

        return [
            'file_path' => $backupFilePath,
            'file_name' => $backupFilename,
            'file_size' => $fileSize,
            'sha256' => $sha256,
        ];
    }

    /**
     * Resolve valid mysqldump path
     */
    protected function getMysqlDumpPath(): string
    {
        $mysqlDumpExe = config('database.mysqldump_path') ?: env('MYSQLDUMP_PATH');
        if ($mysqlDumpExe && File::exists($mysqlDumpExe)) {
            return $mysqlDumpExe;
        }

        $candidates = [
            'C:\\Program Files\\MySQL\\MySQL Server 9.6\\bin\\mysqldump.exe',
            'C:\\Program Files\\MySQL\\MySQL Server 8.4\\bin\\mysqldump.exe',
            'C:\\Program Files\\MySQL\\MySQL Server 8.0\\bin\\mysqldump.exe',
            'C:\\Program Files\\MySQL\\MySQL Workbench 8.0 CE\\mysqldump.exe',
            'E:\\xampp\\mysql\\bin\\mysqldump.exe',
            'C:\\xampp\\mysql\\bin\\mysqldump.exe',
        ];

        foreach ($candidates as $cand) {
            if (File::exists($cand)) {
                return $cand;
            }
        }

        throw new RuntimeException("mysqldump executable not found. Cannot perform safe backup.");
    }
}
