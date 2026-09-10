<?php

namespace App\Services\OpenEmr;

use App\Models\Subscription;
use Exception;
use Illuminate\Support\Facades\File;
use PDO;

class OpenEmrTenantCompatibilityChecker
{
    /**
     * Cached canonical table list and table hash from openemr/sql/database.sql
     */
    protected static ?array $canonicalTables = null;
    protected static ?string $canonicalTableHash = null;

    public function getOpenEmrBasePath(): string
    {
        return rtrim(
            (string) config('oemr.base_path', base_path('oemr')),
            '/\\'
        );
    }

    /**
     * Get or compute the deterministic table hash from bundled database.sql
     */
    public function getCanonicalTableInfo(): array
    {
        if (self::$canonicalTables === null) {
            $databaseSqlPath = $this->getOpenEmrBasePath() . '/sql/database.sql';
            if (!File::exists($databaseSqlPath)) {
                throw new Exception("Canonical database.sql not found at {$databaseSqlPath}");
            }

            $sql = File::get($databaseSqlPath);
            preg_match_all('/CREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?[`\']?([a-zA-Z0-9_]+)[`\']?/i', $sql, $matches);
            $tables = array_values(array_unique($matches[1]));
            sort($tables);

            self::$canonicalTables = $tables;
            self::$canonicalTableHash = hash('sha256', implode('|', $tables));
        }

        return [
            'tables' => self::$canonicalTables,
            'count' => count(self::$canonicalTables),
            'hash' => self::$canonicalTableHash,
        ];
    }

    /**
     * Perform deep read-only compatibility and fingerprint checks on a tenant
     *
     * @param Subscription $subscription
     * @return array
     */
    public function checkCompatibility(Subscription $subscription): array
    {
        $dbName = $subscription->openemr_database;
        $tenantSlug = $subscription->tenant_slug;

        $evidence = [];
        $errors = [];
        $isCompatible = false;
        $classification = 'UNKNOWN';

        // 1. Managed subscription validation
        if (empty($dbName) || !str_starts_with($dbName, 'openemr_site_')) {
            return [
                'classification' => 'UNSAFE_INVALID_TENANT_DB',
                'is_compatible' => false,
                'evidence' => ['Managed DB name missing or does not follow openemr_site_ convention'],
                'errors' => ['Database name not a managed tenant'],
            ];
        }

        $evidence[] = "Managed Subscription #{$subscription->id} confirmed in Laravel application database";

        // 2. Tenant filesystem site directory and configuration validation
        $openEmrSitesPath = $this->getOpenEmrBasePath() . '/sites';
        $siteDir = $openEmrSitesPath . '/' . $tenantSlug;
        $sqlConfPath = $siteDir . '/sqlconf.php';

        if (!File::isDirectory($siteDir)) {
            $errors[] = "Site directory not found: {$siteDir}";
        } else {
            $evidence[] = "Site directory exists: sites/{$tenantSlug}";
        }

        if (!File::exists($sqlConfPath)) {
            $errors[] = "Site configuration sqlconf.php not found in {$siteDir}";
        } else {
            $sqlConfContent = File::get($sqlConfPath);
            if (!str_contains($sqlConfContent, $dbName)) {
                $errors[] = "sqlconf.php does not reference database {$dbName}";
            } else {
                $evidence[] = "sqlconf.php points directly to database {$dbName}";
            }
        }

        // 3. Database connection & existence
        $driver = config('database.default', 'mysql');
        $host = config("database.connections.{$driver}.host", '127.0.0.1');
        $port = config("database.connections.{$driver}.port", '3307');
        $user = config("database.connections.{$driver}.username", 'root');
        $pass = config("database.connections.{$driver}.password", 'root');

        try {
            $pdoRoot = new PDO("mysql:host={$host};port={$port};charset=utf8mb4", $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);
            $stmtDbs = $pdoRoot->query("SHOW DATABASES LIKE " . $pdoRoot->quote($dbName));
            if (!$stmtDbs->fetchColumn()) {
                return [
                    'classification' => 'SKIPPED_DB_DOES_NOT_EXIST',
                    'is_compatible' => false,
                    'evidence' => $evidence,
                    'errors' => ["Database {$dbName} does not exist on MySQL server {$host}:{$port}"],
                ];
            }
            $evidence[] = "Database {$dbName} confirmed online on {$host}:{$port}";

            $pdoTenant = new PDO("mysql:host={$host};port={$port};dbname={$dbName};charset=utf8mb4", $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);

            // 4. Deterministic table set fingerprinting against bundled OpenEMR 8.3.0 database.sql
            $canonicalInfo = $this->getCanonicalTableInfo();
            $stmtTenantTables = $pdoTenant->query("SELECT table_name FROM information_schema.tables WHERE table_schema = " . $pdoTenant->quote($dbName));
            $tenantTables = $stmtTenantTables->fetchAll(PDO::FETCH_COLUMN);
            sort($tenantTables);
            $tenantTableHash = hash('sha256', implode('|', $tenantTables));

            $missingTables = array_diff($canonicalInfo['tables'], $tenantTables);
            $extraTables = array_diff($tenantTables, $canonicalInfo['tables']);

            if (!empty($missingTables) || !empty($extraTables) || $tenantTableHash !== $canonicalInfo['hash']) {
                $errors[] = "Table set fingerprint mismatch (Canonical hash: {$canonicalInfo['hash']}, Tenant hash: {$tenantTableHash}). Missing: " . count($missingTables) . ", Extra: " . count($extraTables);
            } else {
                $evidence[] = "Exact table set fingerprint match: 283/283 OpenEMR 8.3.0 tables verified (SHA256: {$tenantTableHash})";
            }

            // 5. Critical table structure and column verification
            $columnsToCheck = [
                'version' => ['v_major', 'v_minor', 'v_patch', 'v_realpatch', 'v_tag', 'v_database', 'v_acl'],
                'globals' => ['gl_name', 'gl_index', 'gl_value'],
                'users' => ['id', 'username', 'password', 'fname', 'lname', 'authorized', 'active', 'calendar', 'cal_ui', 'facility_id'],
                'users_secure' => ['id', 'username', 'password', 'last_update_password', 'last_update'],
                'gacl_aco' => ['id', 'section_value', 'value', 'order_value', 'name'],
                'gacl_aro' => ['id', 'section_value', 'value', 'order_value', 'name'],
                'gacl_aro_groups' => ['id', 'parent_id', 'name', 'value'],
                'patient_data' => ['id', 'title', 'fname', 'lname', 'DOB', 'sex'],
            ];

            foreach ($columnsToCheck as $tbl => $cols) {
                $stmtCols = $pdoTenant->query("SELECT column_name FROM information_schema.columns WHERE table_schema = " . $pdoTenant->quote($dbName) . " AND table_name = " . $pdoTenant->quote($tbl));
                $existingCols = $stmtCols->fetchAll(PDO::FETCH_COLUMN);
                $missingCols = array_diff($cols, $existingCols);
                if (!empty($missingCols)) {
                    $errors[] = "Table {$tbl} missing expected 8.3.0 columns: " . implode(', ', $missingCols);
                }
            }
            if (empty($errors)) {
                $evidence[] = "Critical 8.3.0 table column signatures verified across version, globals, users, users_secure, gacl, and patient_data";
            }

            // 6. Inspect version row
            $stmtVersion = $pdoTenant->query("SELECT * FROM `version` LIMIT 1");
            $versionRow = $stmtVersion->fetch(PDO::FETCH_ASSOC);

            if (!$versionRow) {
                $errors[] = "version table has no rows";
            } else {
                $vMajor = (int) ($versionRow['v_major'] ?? 0);
                $vMinor = (int) ($versionRow['v_minor'] ?? 0);
                $vPatch = (int) ($versionRow['v_patch'] ?? 0);
                $vDb = (int) ($versionRow['v_database'] ?? 0);
                $vAcl = (int) ($versionRow['v_acl'] ?? 0);

                $evidence[] = "Version row inspected: v_major={$vMajor}, v_minor={$vMinor}, v_patch={$vPatch}, v_database={$vDb}, v_acl={$vAcl}";

                // Case A: Already canonical 8.3.0 synchronized
                if ($vMajor === 8 && $vMinor === 3 && $vPatch === 0 && $vDb === 541 && $vAcl === 13) {
                    $classification = 'CANONICAL_8_3_0_SYNCHRONIZED';
                    $isCompatible = true;
                    $evidence[] = "Tenant database is already fully initialized with canonical OpenEMR 8.3.0 revision 541 / ACL 13";
                }
                // Case B: Legacy 0.0.0 uninitialized baseline
                elseif ($vMajor === 0 && $vMinor === 0 && $vPatch === 0 && $vDb === 0) {
                    // Check v_acl compatibility: 8.3.0 database.sql installs (0, 0, 0, 0, '', 0, 13)
                    if ($vAcl === 13) {
                        $evidence[] = "Baseline database.sql placeholder confirmed: 0.0.0 rev 0 with canonical ACL revision 13";
                        if (empty($errors)) {
                            $classification = 'LEGACY_8_3_0_BASELINE_COMPATIBLE';
                            $isCompatible = true;
                        }
                    } elseif ($vAcl === 0) {
                        $evidence[] = "Baseline database.sql unstamped placeholder confirmed: 0.0.0 rev 0 with unstamped v_acl 0";
                        if (empty($errors)) {
                            $classification = 'LEGACY_8_3_0_ACL_UNSTAMPED';
                            $isCompatible = true;
                        }
                    } else {
                        $errors[] = "Baseline v_acl mismatch: expected canonical baseline 13 or unstamped 0, found {$vAcl}";
                        $classification = 'UNSAFE_INCOMPATIBLE_ACL_VERSION';
                    }
                } else {
                    $errors[] = "Unsupported or alien schema version: {$vMajor}.{$vMinor}.{$vPatch} (rev {$vDb}, acl {$vAcl})";
                    $classification = 'UNSAFE_UNSUPPORTED_SCHEMA_VERSION';
                }
            }

        } catch (Exception $e) {
            $errors[] = "Database connectivity / inspection exception: " . $e->getMessage();
            $classification = 'UNSAFE_DATABASE_EXCEPTION';
        }

        if (!empty($errors) && $classification === 'UNKNOWN') {
            $classification = 'UNSAFE_INCOMPATIBLE_SCHEMA';
        }

        return [
            'classification' => $classification,
            'is_compatible' => $isCompatible,
            'evidence' => $evidence,
            'errors' => $errors,
            'version_row' => $versionRow ?? null,
        ];
    }
}

