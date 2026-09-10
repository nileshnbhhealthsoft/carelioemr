<?php

namespace App\Services;

use App\Models\Subscription;
use App\Services\OpenEmr\OpenEmrCanonicalAclSeeder;
use App\Services\OpenEmr\OpenEmrCanonicalGlobalsLoader;
use App\Services\OpenEmr\OpenEmrTenantAuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use PDO;
use RuntimeException;
use Throwable;

class OpenEmrProvisioningService
{
    public function __construct(
        protected OpenEmrCanonicalGlobalsLoader $globalsLoader,
        protected OpenEmrCanonicalAclSeeder $aclSeeder,
        protected OpenEmrTenantAuditService $auditService
    ) {}

    /**
     * Get path to OpenEMR base directory safely across CLI and web contexts
     */
    public function getOpenEmrBasePath(): string
    {
        try {
            if (function_exists('base_path') && app()->has('path.base')) {
                return base_path('oemr');
            }
        } catch (Throwable $e) {}
        return 'E:/xampp/htdocs/1page/oemr';
    }

    /**
     * Establish a PDO connection to a target tenant database
     */
    protected function getTenantPdo(string $dbName): PDO
    {
        $driver = config('database.default', 'mysql');
        $host = config("database.connections.{$driver}.host", '127.0.0.1');
        $port = config("database.connections.{$driver}.port", '3307');
        $user = config("database.connections.{$driver}.username", 'root');
        $pass = config("database.connections.{$driver}.password", 'root');

        $pdo = new PDO("mysql:host={$host};port={$port};dbname={$dbName};charset=utf8mb4", $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        return $pdo;
    }

    /**
     * Provision isolated Multi-Tenant OpenEMR Instance 100% canonically
     */
    public function provisionTenant(Subscription $subscription): bool
    {
        $cleanSlug = Str::slug($subscription->doctor_name, '-');
        $tenantSlug = 'site-' . ($cleanSlug ?: 'tenant') . '-' . $subscription->id;
        $dbName = 'openemr_' . str_replace('-', '_', $tenantSlug);
        $openEmrBasePath = $this->getOpenEmrBasePath();
        $sitePath = $openEmrBasePath . '/sites/' . $tenantSlug;
        $siteUrl = config('app.url', 'http://localhost:8000') . '/oemr/interface/login/login.php?site=' . $tenantSlug;

        Log::info("Starting Canonical OpenEMR Tenant Provisioning for Subscription #{$subscription->id} ({$tenantSlug})");

        // Update status to provisioning
        $subscription->update([
            'tenant_slug' => $tenantSlug,
            'openemr_database' => $dbName,
            'openemr_site_url' => $siteUrl,
            'provision_status' => 'provisioning',
            'provision_error' => null,
        ]);

        try {
            // Stage 1: Create isolated tenant site directory with strict path guards
            $this->createTenantDirectory($openEmrBasePath, $sitePath);

            // Stage 2: Create isolated tenant database
            $this->createTenantDatabase($dbName);

            // Stage 3: Generate Tenant sqlconf.php
            $this->generateSqlConf($sitePath, $dbName, $tenantSlug);

            // Stage 4: Import baseline openemr/sql/database.sql
            $this->importDatabaseSql($dbName);

            // Stage 5: Apply canonical OpenEMR version information (Installer::add_version_info() behavior)
            $this->applyCanonicalVersion($dbName);

            // Stage 6: Seed canonical globals dynamically using OpenEmrCanonicalGlobalsLoader
            $this->seedCanonicalGlobals($dbName, $sitePath, $tenantSlug);

            // Stage 7 & 8: Initialize canonical ACL/phpGACL baseline & official service accounts
            $this->seedCanonicalAcl($sitePath, $tenantSlug, $dbName);

            // Stage 9: Apply tenant-specific overrides (clinic name, phone, etc.)
            $this->applyTenantOverrides($dbName, $subscription);

            // Stage 10: Create tenant first Admin user & map to canonical Admin ACL group
            $this->createFirstAdminUserAndMapAcl($dbName, $subscription);

            // Stage 11: Run health checks
            $this->verifyTenantHealth($subscription);

            // Stage 12: Mark completed
            $subscription->update([
                'provision_status' => 'completed',
                'provision_error' => null,
            ]);

            Log::info("Successfully Provisioned Canonical OpenEMR Tenant for #{$subscription->id} at {$siteUrl}");
            return true;

        } catch (Throwable $e) {
            Log::error("Canonical OpenEMR Tenant Provisioning Failed for #{$subscription->id}: " . $e->getMessage());

            // Rollback dynamically on failure with strict safety guards
            $this->rollbackProvisioning($sitePath, $dbName);

            $subscription->update([
                'provision_status' => 'failed',
                'provision_error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Copy default site template directory dynamically with path guards
     */
    protected function createTenantDirectory(string $openEmrBasePath, string $sitePath): void
    {
        $normalizedSites = str_replace('\\', '/', realpath($openEmrBasePath . '/sites') ?: ($openEmrBasePath . '/sites'));
        $normalizedSitePath = str_replace('\\', '/', $sitePath);

        if (!str_starts_with($normalizedSitePath, $normalizedSites . '/site-')) {
            throw new RuntimeException("Security violation: Target site directory outside sites/ container: {$sitePath}");
        }

        if (File::exists($sitePath)) {
            File::deleteDirectory($sitePath);
        }

        $templatePath = $openEmrBasePath . '/sites/default';

        if (File::exists($templatePath)) {
            File::copyDirectory($templatePath, $sitePath);
        } else {
            File::makeDirectory($sitePath, 0755, true, true);
            File::makeDirectory($sitePath . '/documents', 0755, true, true);
            File::makeDirectory($sitePath . '/edi', 0755, true, true);
            File::makeDirectory($sitePath . '/era', 0755, true, true);
            File::makeDirectory($sitePath . '/letter_templates', 0755, true, true);
        }

        if (!File::exists($sitePath . '/config.php') && File::exists($templatePath . '/config.php')) {
            File::copy($templatePath . '/config.php', $sitePath . '/config.php');
        }
    }

    /**
     * Create isolated database for tenant dynamically with safety guard
     */
    protected function createTenantDatabase(string $dbName): void
    {
        if (!str_starts_with($dbName, 'openemr_site_')) {
            throw new RuntimeException("Security violation: Target database must start with 'openemr_site_': {$dbName}");
        }

        $driver = config('database.default', 'mysql');

        if ($driver === 'mysql') {
            DB::statement("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        } else {
            $sqlitePath = database_path("{$dbName}.sqlite");
            if (!File::exists($sqlitePath)) {
                File::put($sqlitePath, '');
            }
        }
    }

    /**
     * Generate sqlconf.php dynamically reading config values
     */
    protected function generateSqlConf(string $sitePath, string $dbName, string $tenantSlug): void
    {
        $driver = config('database.default', 'mysql');
        $host = config("database.connections.{$driver}.host", '127.0.0.1');
        $port = config("database.connections.{$driver}.port", '3307');
        $user = config("database.connections.{$driver}.username", 'root');
        $pass = config("database.connections.{$driver}.password", 'root');

        $sqlConfContent = "<?php\n" .
            "// OpenEMR Tenant Configuration Dynamically Generated by Laravel\n" .
            "\$host = '{$host}';\n" .
            "\$port = '{$port}';\n" .
            "\$login = '{$user}';\n" .
            "\$pass = '{$pass}';\n" .
            "\$dbase = '{$dbName}';\n" .
            "\$site_id = '{$tenantSlug}';\n" .
            "\$config = 1;\n\n" .
            "\$sqlconf = array();\n" .
            "global \$sqlconf;\n" .
            "\$sqlconf['host'] = \$host;\n" .
            "\$sqlconf['port'] = \$port;\n" .
            "\$sqlconf['login'] = \$login;\n" .
            "\$sqlconf['pass'] = \$pass;\n" .
            "\$sqlconf['dbase'] = \$dbase;\n";

        File::put($sitePath . '/sqlconf.php', $sqlConfContent);
    }

    /**
     * Import baseline openemr/sql/database.sql schema
     */
    protected function importDatabaseSql(string $dbName): void
    {
        $driver = config('database.default', 'mysql');
        if ($driver !== 'mysql') {
            return;
        }

        $host = config('database.connections.mysql.host', '127.0.0.1');
        $port = config('database.connections.mysql.port', '3307');
        $user = config('database.connections.mysql.username', 'root');
        $pass = config('database.connections.mysql.password', 'root');

        $sqlFile = $this->getOpenEmrBasePath() . '/sql/database.sql';
        if (!File::exists($sqlFile)) {
            throw new RuntimeException("Canonical OpenEMR database.sql not found: {$sqlFile}");
        }

        $mysqlExe = 'C:\\Program Files\\MySQL\\MySQL Server 9.6\\bin\\mysql.exe';
        if (File::exists($mysqlExe)) {
            $passArg = $pass !== '' ? "-p{$pass}" : '';
            $cmd = "cmd.exe /c \"\"{$mysqlExe}\" -h {$host} -P {$port} -u {$user} {$passArg} {$dbName} < \"{$sqlFile}\"\"";
            exec($cmd, $output, $returnCode);
            if ($returnCode !== 0) {
                throw new RuntimeException("MySQL CLI execution of database.sql failed with exit code {$returnCode}");
            }
        } else {
            $pdo = $this->getTenantPdo($dbName);
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
            $rawSql = File::get($sqlFile);
            $pdo->setAttribute(PDO::MYSQL_ATTR_MULTI_STATEMENTS, true);
            $pdo->exec($rawSql);
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
        }
    }

    /**
     * Apply canonical OpenEMR version information from openemr/version.php
     * Exactly reproducing Installer::add_version_info()
     */
    protected function applyCanonicalVersion(string $dbName): void
    {
        $versionFile = $this->getOpenEmrBasePath() . '/version.php';
        if (!File::exists($versionFile)) {
            throw new RuntimeException("Canonical OpenEMR version.php not found at {$versionFile}");
        }

        // Include canonical version definitions
        include $versionFile;

        $pdo = $this->getTenantPdo($dbName);
        $stmtVer = $pdo->prepare("UPDATE version SET v_major = ?, v_minor = ?, v_patch = ?, v_realpatch = ?, v_tag = ?, v_database = ?, v_acl = ?");
        $stmtVer->execute([$v_major, $v_minor, $v_patch, $v_realpatch, $v_tag, $v_database, $v_acl]);

        Log::info("Canonical OpenEMR version {$v_major}.{$v_minor}.{$v_patch} (rev {$v_database}, acl {$v_acl}) applied to {$dbName}");
    }

    /**
     * Seed canonical globals dynamically using OpenEmrCanonicalGlobalsLoader
     */
    protected function seedCanonicalGlobals(string $dbName, string $sitePath, string $tenantSlug): void
    {
        $canonicalInfo = $this->globalsLoader->loadCanonicalGlobals();
        $pdo = $this->getTenantPdo($dbName);

        $stmtGlob = $pdo->prepare("INSERT INTO globals (gl_name, gl_index, gl_value) VALUES (?, 0, ?) ON DUPLICATE KEY UPDATE gl_name = gl_name");

        foreach ($canonicalInfo['canonical_globals'] as $key => $meta) {
            $value = $meta['default'];

            // Dynamically resolve environment and path-dependent globals
            if ($key === 'OE_SITE_DIR') {
                $value = $sitePath;
            } elseif ($key === 'documents_path') {
                $value = $sitePath . '/documents';
            } elseif ($key === 'edi_history_path') {
                $value = $sitePath . '/edi';
            } elseif ($key === 'temporary_files_dir') {
                $value = $sitePath . '/documents/temp';
            } elseif ($key === 'site_id') {
                $value = $tenantSlug;
            } elseif ($key === 'installation_id' && empty($value)) {
                $value = (string) Str::uuid();
            } elseif ($key === 'system_uuid' && empty($value)) {
                $value = (string) Str::uuid();
            } elseif ($key === 'openemr_unique_installation_id' && empty($value)) {
                $value = hash('sha256', $sitePath . microtime());
            }

            $stmtGlob->execute([$key, $value]);
        }

        Log::info("Seeded {$canonicalInfo['total_discovered']} canonical globals into {$dbName}");
    }

    /**
     * Initialize canonical ACL and official service accounts via OpenEmrCanonicalAclSeeder
     */
    protected function seedCanonicalAcl(string $sitePath, string $tenantSlug, string $dbName): void
    {
        $this->aclSeeder->seedCanonicalAcl($sitePath, $tenantSlug, $dbName, 'admin', 'Billy Smith');
    }

    /**
     * Apply tenant-specific business overrides AFTER canonical defaults
     */
    protected function applyTenantOverrides(string $dbName, Subscription $subscription): void
    {
        $pdo = $this->getTenantPdo($dbName);

        $clinicName = $subscription->practice_type 
            ? ($subscription->practice_type . ' Clinic') 
            : ($subscription->doctor_name ? ($subscription->doctor_name . ' Practice') : 'CarelioEMR Medical Practice');

        $overrides = [
            'practice_name' => $clinicName,
            'phone' => $subscription->phone ?? '',
            'css_header' => 'style_light.css',
            'show_primary_logo' => '1',
            'primary_logo_width' => 'w-50',
            'logo_position' => 'flex-column',
            'show_tagline_on_login' => '1',
            'login_tagline_text' => 'CarelioEMR - Advanced Clinical & Medical Practice Management EHR',
            'show_labels_on_login_form' => '1',
            'language_menu_login' => '1',
            'language_menu_showall' => '1',
            'display_acknowledgements_on_login' => '1',
            'login_page_layout' => 'login/layouts/vertical_band.html.twig',
            'timeout' => '14400',
            'portal_timeout' => '1800',
            'calendar_view_type' => 'day',
            'calendar_interval' => '15',
            'schedule_start' => '8',
            'schedule_end' => '18',
        ];

        $stmtOverride = $pdo->prepare("REPLACE INTO globals (gl_name, gl_index, gl_value) VALUES (?, 0, ?)");
        foreach ($overrides as $gk => $gv) {
            $stmtOverride->execute([$gk, (string) $gv]);
        }

        // Configure facility primary business entity
        $stmtFac = $pdo->prepare("UPDATE facility SET name = ?, color = '#99FFFF' WHERE primary_business_entity = 1 OR id = 3");
        $stmtFac->execute([$clinicName]);

        // Configure default tabs (Calendar & Message Center)
        $pdo->exec("UPDATE list_options SET activity = 1 WHERE list_id = 'default_open_tabs' AND option_id IN ('cal', 'msg')");
        $pdo->exec("UPDATE list_options SET activity = 0 WHERE list_id = 'default_open_tabs' AND option_id NOT IN ('cal', 'msg')");

        // Insert Spanish language for language menu support
        $pdo->exec("INSERT IGNORE INTO lang_languages (lang_id, lang_code, lang_description, lang_is_rtl) VALUES (2, 'es', 'Spanish (Latin American)', 0)");
    }

    /**
     * Create the first tenant Admin user and link to canonical Admin ACL group
     */
    protected function createFirstAdminUserAndMapAcl(string $dbName, Subscription $subscription): void
    {
        $pdo = $this->getTenantPdo($dbName);

        // 1. Seed Demo 'admin' user as Billy Smith (password: 'pass')
        $adminPassHash = password_hash('pass', PASSWORD_DEFAULT);
        $adminId = $pdo->query("SELECT id FROM users WHERE username = 'admin'")->fetchColumn();

        if (!$adminId) {
            $stmtAdmin = $pdo->prepare("INSERT INTO users (username, password, fname, lname, authorized, active, calendar, cal_ui, facility_id, info, date_created, last_updated) 
                VALUES ('admin', 'NoLongerUsed', 'Billy', 'Smith', 1, 1, 1, 3, 3, 'Administrator', NOW(), NOW())");
            $stmtAdmin->execute();
            $adminId = (int) $pdo->lastInsertId();
        } else {
            $adminId = (int) $adminId;
            $pdo->exec("UPDATE users SET fname = 'Billy', lname = 'Smith', authorized = 1, active = 1, calendar = 1, cal_ui = 3, facility_id = 3, last_updated = NOW() WHERE id = {$adminId}");
        }

        $pdo->exec("REPLACE INTO users_secure (id, username, password, last_update_password, last_update) VALUES ({$adminId}, 'admin', '{$adminPassHash}', NOW(), NOW())");
        $pdo->exec("INSERT IGNORE INTO `groups` (name, user) VALUES ('Default', 'admin')");

        // Ensure admin user is mapped into canonical Admin ACL group
        $this->aclSeeder->ensureAdminUserAclMapped($dbName, 'admin', 'Billy Smith');

        // 2. Seed subscriber doctor user if different from admin
        if (!empty($subscription->doctor_name)) {
            $username = Str::slug($subscription->doctor_name, '_') ?: ('doctor_' . $subscription->id);
            $hashedPassword = password_hash('ClinicPass123!', PASSWORD_DEFAULT);
            $nameParts = explode(' ', trim($subscription->doctor_name));
            $fname = $nameParts[0] ?? 'Doctor';
            $lname = implode(' ', array_slice($nameParts, 1)) ?: 'Subscriber';
            $facilityName = ($subscription->practice_type ?? 'Clinic') . ' - ' . ($subscription->region ?? 'Global');

            $existingId = $pdo->query("SELECT id FROM users WHERE username = '{$username}'")->fetchColumn();
            if (!$existingId) {
                $stmtDoc = $pdo->prepare("INSERT INTO users (username, password, fname, lname, email, facility, authorized, active, calendar, cal_ui, facility_id, info, date_created, last_updated) 
                    VALUES (?, 'NoLongerUsed', ?, ?, ?, ?, 1, 1, 1, 3, 3, 'Practice Manager / Clinician', NOW(), NOW())");
                $stmtDoc->execute([$username, $fname, $lname, $subscription->email, $facilityName]);
                $docUserId = (int) $pdo->lastInsertId();
            } else {
                $docUserId = (int) $existingId;
            }

            $pdo->exec("REPLACE INTO users_secure (id, username, password, last_update_password, last_update) VALUES ({$docUserId}, '{$username}', '{$hashedPassword}', NOW(), NOW())");
            $pdo->exec("INSERT IGNORE INTO `groups` (name, user) VALUES ('Default', '{$username}')");

            // Map subscriber doctor into canonical admin group as well
            $this->aclSeeder->ensureAdminUserAclMapped($dbName, $username, $subscription->doctor_name);
        }
    }

    /**
     * Run rigorous health checks on the newly provisioned tenant
     */
    protected function verifyTenantHealth(Subscription $subscription): void
    {
        $audit = $this->auditService->auditTenant($subscription);

        if (!$audit['version_compatible']) {
            throw new RuntimeException("Health check failed: Version verification failed (" . implode(', ', $audit['compatibility_errors']) . ")");
        }

        if (($audit['health_invariants']['theme_tabs_layout']['status'] ?? '') !== 'PASS') {
            throw new RuntimeException("Health check failed: theme_tabs_layout invariant not passed (value: " . ($audit['health_invariants']['theme_tabs_layout']['value'] ?? 'none') . ")");
        }

        if (($audit['health_invariants']['full_new_patient_form']['status'] ?? '') !== 'PASS') {
            throw new RuntimeException("Health check failed: full_new_patient_form invariant not passed (value: " . ($audit['health_invariants']['full_new_patient_form']['value'] ?? 'none') . ")");
        }

        if (empty($audit['admin_user_audit']['gacl_mapped'])) {
            throw new RuntimeException("Health check failed: Admin user is not mapped to canonical Admin ACL group");
        }

        $missingSafe = count($audit['missing_globals_by_category']['safe_static_default']);
        if ($missingSafe > 0) {
            throw new RuntimeException("Health check failed: {$missingSafe} canonical safe default globals are missing");
        }

        Log::info("All canonical health checks passed successfully for #{$subscription->id}");
    }

    /**
     * Rollback provisioning resources dynamically on failure with strict path and database guards
     */
    protected function rollbackProvisioning(string $sitePath, string $dbName): void
    {
        $openEmrBasePath = $this->getOpenEmrBasePath();
        $normalizedSites = str_replace('\\', '/', realpath($openEmrBasePath . '/sites') ?: ($openEmrBasePath . '/sites'));
        $normalizedSitePath = str_replace('\\', '/', $sitePath);

        // Safety Guard 1: Only delete directories strictly within oemr/sites/site-*
        if (str_starts_with($normalizedSitePath, $normalizedSites . '/site-') && File::exists($sitePath)) {
            Log::warning("Rollback: Deleting failed tenant site directory: {$sitePath}");
            File::deleteDirectory($sitePath);
        }

        // Safety Guard 2: Only drop databases strictly matching openemr_site_* and never system/reference DBs
        if (str_starts_with($dbName, 'openemr_site_') && !in_array($dbName, ['openemr', 'auraemr', 'mysql', 'information_schema', 'performance_schema', 'sys'], true)) {
            try {
                Log::warning("Rollback: Dropping failed tenant database: {$dbName}");
                DB::statement("DROP DATABASE IF EXISTS `{$dbName}`");
            } catch (Throwable $e) {
                Log::warning("Rollback DB drop warning: " . $e->getMessage());
            }
        }
    }
}
