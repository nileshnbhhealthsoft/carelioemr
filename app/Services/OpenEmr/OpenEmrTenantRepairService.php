<?php

namespace App\Services\OpenEmr;

use App\Models\Subscription;
use Exception;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use PDO;
use RuntimeException;

class OpenEmrTenantRepairService
{
    protected OpenEmrTenantCompatibilityChecker $checker;
    protected OpenEmrCanonicalGlobalsLoader $globalsLoader;
    protected OpenEmrCanonicalAclSeeder $aclSeeder;
    protected OpenEmrTenantAuditService $auditService;

    public function __construct(
        OpenEmrTenantCompatibilityChecker $checker,
        OpenEmrCanonicalGlobalsLoader $globalsLoader,
        OpenEmrCanonicalAclSeeder $aclSeeder,
        OpenEmrTenantAuditService $auditService
    ) {
        $this->checker = $checker;
        $this->globalsLoader = $globalsLoader;
        $this->aclSeeder = $aclSeeder;
        $this->auditService = $auditService;
    }

    public function getOpenEmrBasePath(): string
    {
        return rtrim(
            (string) config('oemr.base_path', base_path('oemr')),
            '/\\'
        );
    }

    /**
     * Execute a safe, verified canary repair on a single legacy tenant
     *
     * @param Subscription $subscription
     * @return array
     */
    public function repairTenant(Subscription $subscription): array
    {
        $dbName = $subscription->openemr_database;
        $tenantSlug = $subscription->tenant_slug;

        // Security Guard: Check subscription and DB name conventions
        if (empty($dbName) || !str_starts_with($dbName, 'openemr_site_')) {
            throw new RuntimeException("Security violation: Database {$dbName} is not a valid tenant database");
        }

        // Step 1: Compatibility Verification
        $compat = $this->checker->checkCompatibility($subscription);
        $eligibleClassifications = ['LEGACY_8_3_0_BASELINE_COMPATIBLE', 'LEGACY_8_3_0_ACL_UNSTAMPED', 'CANONICAL_8_3_0_SYNCHRONIZED'];
        if (!$compat['is_compatible'] || !in_array($compat['classification'], $eligibleClassifications)) {
            throw new RuntimeException("Tenant #{$subscription->id} is not eligible for repair: classification is '{$compat['classification']}'");
        }

        $pdo = $this->getTenantPdo($dbName);

        // Step 2: Pre-Repair Snapshot
        $preRepairSnapshot = $this->captureSnapshot($pdo, $subscription);

        // Step 3: Mandatory Pre-Repair Backup
        $backupResult = $this->createVerifiedBackup($subscription);

        $repairRunId = (string) Str::uuid();
        $auditLog = [];
        $startTime = microtime(true);

        try {
            // Step 4: Version Repair (Guarded)
            $versionChange = $this->repairVersion($pdo, $repairRunId, $subscription->id, $dbName, $auditLog);

            // Step 5: Canonical Globals Repair (Additive Only)
            $globalsResult = $this->repairGlobals($pdo, $repairRunId, $subscription, $dbName, $auditLog);

            // Step 6: Canonical ACL Repair
            $aclResult = $this->repairAcl($pdo, $repairRunId, $subscription, $dbName, $auditLog);

            $duration = round(microtime(true) - $startTime, 2);

            // Step 7: Post-Repair Verification Audit
            $postRepairAudit = $this->auditService->auditTenant($subscription);
            $postRepairSnapshot = $this->captureSnapshot($pdo, $subscription);

            // Verify Data Preservation
            $this->verifyDataPreservation($preRepairSnapshot, $postRepairSnapshot);

            return [
                'success' => true,
                'repair_run_id' => $repairRunId,
                'subscription_id' => $subscription->id,
                'doctor_name' => $subscription->doctor_name,
                'database' => $dbName,
                'tenant_slug' => $tenantSlug,
                'duration_seconds' => $duration,
                'backup' => $backupResult,
                'pre_repair_snapshot' => $preRepairSnapshot,
                'post_repair_snapshot' => $postRepairSnapshot,
                'version_change' => $versionChange,
                'globals_result' => $globalsResult,
                'acl_result' => $aclResult,
                'audit_log' => $auditLog,
                'post_repair_audit' => $postRepairAudit,
            ];

        } catch (Exception $e) {
            Log::error("Canary repair failed for #{$subscription->id} ({$dbName}): " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Capture complete read-only snapshot of tenant state
     */
    protected function captureSnapshot(PDO $pdo, Subscription $subscription): array
    {
        $dbName = $subscription->openemr_database;
        $vRow = $pdo->query("SELECT * FROM version LIMIT 1")->fetch(PDO::FETCH_ASSOC);

        $existingGlobals = $pdo->query("SELECT gl_name, gl_value FROM globals")->fetchAll(PDO::FETCH_KEY_PAIR);
        $canonicalInfo = $this->globalsLoader->loadCanonicalGlobals();
        $missingCount = 0;
        foreach ($canonicalInfo['canonical_globals'] as $gk => $gData) {
            if (!array_key_exists($gk, $existingGlobals)) {
                $missingCount++;
            }
        }

        $themeTabs = $existingGlobals['theme_tabs_layout'] ?? 'MISSING';
        $fullPatient = $existingGlobals['full_new_patient_form'] ?? 'MISSING';

        $gaclTables = ['gacl_aco_sections', 'gacl_aro_sections', 'gacl_aco', 'gacl_aro', 'gacl_aro_groups', 'gacl_aco_map', 'gacl_groups_aro_map', 'gacl_acl'];
        $aclCounts = [];
        foreach ($gaclTables as $tbl) {
            $aclCounts[$tbl] = (int) $pdo->query("SELECT COUNT(*) FROM {$tbl}")->fetchColumn();
        }

        $adminAroId = $pdo->query("SELECT id FROM gacl_aro WHERE section_value = 'users' AND value = 'admin' LIMIT 1")->fetchColumn();
        $adminGroupId = $pdo->query("SELECT id FROM gacl_aro_groups WHERE value = 'admin' OR name = 'Administrators' ORDER BY id ASC LIMIT 1")->fetchColumn();
        $adminMapped = false;
        if ($adminAroId && $adminGroupId) {
            $stmtM = $pdo->prepare("SELECT 1 FROM gacl_groups_aro_map WHERE group_id = ? AND aro_id = ? LIMIT 1");
            $stmtM->execute([$adminGroupId, $adminAroId]);
            $adminMapped = (bool) $stmtM->fetchColumn();
        }

        return [
            'version' => $vRow,
            'globals_total' => count($existingGlobals),
            'globals_missing' => $missingCount,
            'theme_tabs_layout' => $themeTabs,
            'full_new_patient_form' => $fullPatient,
            'acl_counts' => $aclCounts,
            'gacl_acl_count' => (int) $pdo->query("SELECT COUNT(*) FROM gacl_acl")->fetchColumn(),
            'gacl_aco_map_count' => (int) $pdo->query("SELECT COUNT(*) FROM gacl_aco_map")->fetchColumn(),
            'gacl_aro_groups_map_count' => (int) $pdo->query("SELECT COUNT(*) FROM gacl_aro_groups_map")->fetchColumn(),
            'gacl_groups_aro_map_count' => (int) $pdo->query("SELECT COUNT(*) FROM gacl_groups_aro_map")->fetchColumn(),
            'admin_mapped' => $adminMapped,
            'users_count' => (int) $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
            'patients_count' => (int) $pdo->query("SELECT COUNT(*) FROM patient_data")->fetchColumn(),
            'encounters_count' => (int) $pdo->query("SELECT COUNT(*) FROM form_encounter")->fetchColumn(),
            'facilities_count' => (int) $pdo->query("SELECT COUNT(*) FROM facility")->fetchColumn(),
            'events_count' => (int) $pdo->query("SELECT COUNT(*) FROM openemr_postcalendar_events")->fetchColumn(),
        ];
    }

    /**
     * Create verified, non-empty database backup using mysqldump
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

        $mysqlDumpExe = 'C:\\Program Files\\MySQL\\MySQL Server 9.6\\bin\\mysqldump.exe';
        if (!File::exists($mysqlDumpExe)) {
            $mysqlDumpExe = 'E:\\xampp\\mysql\\bin\\mysqldump.exe';
        }

        if (!File::exists($mysqlDumpExe)) {
            throw new RuntimeException("mysqldump executable not found. Cannot perform safe backup.");
        }

        $driver = config('database.default', 'mysql');
        $host = config("database.connections.{$driver}.host", '127.0.0.1');
        $port = config("database.connections.{$driver}.port", '3307');
        $user = config("database.connections.{$driver}.username", 'root');
        $pass = config("database.connections.{$driver}.password", 'root');

        $passArg = $pass !== '' ? "-p{$pass}" : '';
        $cmd = "cmd.exe /c \"\"{$mysqlDumpExe}\" -h {$host} -P {$port} -u {$user} {$passArg} --set-gtid-purged=OFF --single-transaction --quick --routines --triggers {$dbName} > \"{$backupFilePath}\"\"";

        exec($cmd, $output, $exitCode);

        // Verification Stage
        if ($exitCode !== 0) {
            if (File::exists($backupFilePath)) File::delete($backupFilePath);
            throw new RuntimeException("mysqldump failed with exit code {$exitCode} for {$dbName}");
        }

        if (!File::exists($backupFilePath)) {
            throw new RuntimeException("Backup file was not created: {$backupFilePath}");
        }

        $fileSize = filesize($backupFilePath);
        if ($fileSize < 50000) { // Standard OpenEMR baseline dump is ~1.5MB to 2.5MB
            File::delete($backupFilePath);
            throw new RuntimeException("Backup file size is suspiciously small ({$fileSize} bytes). Verification failed.");
        }

        // Verify dump content contains key table definitions
        $sampleContent = File::get($backupFilePath);
        if (!str_contains($sampleContent, 'CREATE TABLE `version`') || !str_contains($sampleContent, 'CREATE TABLE `users`')) {
            File::delete($backupFilePath);
            throw new RuntimeException("Backup file failed SQL content validation: missing expected OpenEMR table statements.");
        }

        $sha256 = hash_file('sha256', $backupFilePath);

        Log::info("Successfully created and verified backup for {$dbName}: {$backupFilename} ({$fileSize} bytes, SHA256: {$sha256})");

        return [
            'file_path' => $backupFilePath,
            'file_name' => $backupFilename,
            'file_size' => $fileSize,
            'sha256' => $sha256,
            'timestamp' => $timestamp,
            'verified' => true,
        ];
    }

    /**
     * Guarded Version Repair
     */
    protected function repairVersion(PDO $pdo, string $runId, int $subId, string $dbName, array &$auditLog): array
    {
        $current = $pdo->query("SELECT v_major, v_minor, v_patch, v_database, v_acl FROM version LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        if ((int) $current['v_database'] === 541 && (int) $current['v_major'] === 8) {
            return [
                'rows_affected' => 0,
                'old_version' => json_encode($current),
                'new_version' => json_encode($current),
            ];
        }
        if ((int) $current['v_database'] !== 0) {
            throw new RuntimeException("Guarded update aborted: version.v_database is not 0 (found: {$current['v_database']})");
        }

        $oldState = json_encode($current);
        $newState = json_encode(['v_major' => 8, 'v_minor' => 3, 'v_patch' => 0, 'v_database' => 541, 'v_acl' => 13, 'v_tag' => '']);

        $stmt = $pdo->prepare("UPDATE version SET v_major = 8, v_minor = 3, v_patch = 0, v_database = 541, v_acl = 13, v_tag = '' WHERE v_database = 0");
        $stmt->execute();
        $affected = $stmt->rowCount();

        $auditLog[] = [
            'repair_run_id' => $runId,
            'subscription_id' => $subId,
            'database' => $dbName,
            'table' => 'version',
            'record_key' => 'v_database=0',
            'operation' => 'UPDATE',
            'old_state' => $oldState,
            'new_state' => $newState,
            'timestamp' => date('Y-m-d H:i:s'),
        ];

        return [
            'rows_affected' => $affected,
            'old_version' => $oldState,
            'new_version' => $newState,
        ];
    }

    /**
     * Additive-only Canonical Globals Repair
     */
    protected function repairGlobals(PDO $pdo, string $runId, Subscription $subscription, string $dbName, array &$auditLog): array
    {
        $canonicalInfo = $this->globalsLoader->loadCanonicalGlobals();
        $sitePath = $this->getOpenEmrBasePath() . '/sites/' . $subscription->tenant_slug;

        $existingGlobals = $pdo->query("SELECT gl_name FROM globals")->fetchAll(PDO::FETCH_COLUMN);
        $existingMap = array_flip($existingGlobals);

        $stmtInsert = $pdo->prepare("INSERT IGNORE INTO globals (gl_name, gl_index, gl_value) VALUES (?, 0, ?)");
        $insertedKeys = [];

        foreach ($canonicalInfo['canonical_globals'] as $gk => $gData) {
            if (isset($existingMap[$gk])) {
                continue; // Zero overwrite of existing tenant values
            }

            $value = $gData['default'];

            // Dynamic installation values
            if ($gk === 'webserver_root') {
                $value = str_replace('\\', '/', $this->getOpenEmrBasePath());
            } elseif ($gk === 'web_root') {
                $value = '/' . trim((string) config('oemr.web_path', '/oemr'), '/');
            } elseif ($gk === 'temporary_files_dir') {
                $value = $sitePath . '/documents/temp';
            } elseif ($gk === 'site_id') {
                $value = $subscription->tenant_slug;
            } elseif ($gk === 'installation_id' && empty($value)) {
                $value = (string) Str::uuid();
            } elseif ($gk === 'system_uuid' && empty($value)) {
                $value = (string) Str::uuid();
            } elseif ($gk === 'openemr_unique_installation_id' && empty($value)) {
                $value = hash('sha256', $sitePath . microtime());
            }

            $stmtInsert->execute([$gk, $value]);
            $insertedKeys[] = $gk;

            $auditLog[] = [
                'repair_run_id' => $runId,
                'subscription_id' => $subscription->id,
                'database' => $dbName,
                'table' => 'globals',
                'record_key' => $gk,
                'operation' => 'INSERT',
                'old_state' => null,
                'new_state' => $value,
                'timestamp' => date('Y-m-d H:i:s'),
            ];
        }

        return [
            'total_inserted' => count($insertedKeys),
            'sample_inserted' => array_slice($insertedKeys, 0, 10),
            'theme_tabs_layout_inserted' => in_array('theme_tabs_layout', $insertedKeys),
            'full_new_patient_form_inserted' => in_array('full_new_patient_form', $insertedKeys),
        ];
    }

    /**
     * Canonical ACL Repair
     */
    protected function repairAcl(PDO $pdo, string $runId, Subscription $subscription, string $dbName, array &$auditLog): array
    {
        $insertedAcl = [];
        $insertedAcoMappingsCount = 0;

        // 1. Ensure 'users' section in gacl_aro_sections
        $usersSectionExists = (bool) $pdo->query("SELECT 1 FROM gacl_aro_sections WHERE value = 'users' LIMIT 1")->fetchColumn();
        if (!$usersSectionExists) {
            $nextSecId = (int) $pdo->query("SELECT COALESCE(MAX(id), 0) + 1 FROM gacl_aro_sections")->fetchColumn();
            $pdo->exec("INSERT INTO gacl_aro_sections (id, value, order_value, name, hidden) VALUES ({$nextSecId}, 'users', 10, 'Users', 0)");
            $insertedAcl[] = "gacl_aro_sections: users";
            $auditLog[] = ['repair_run_id' => $runId, 'subscription_id' => $subscription->id, 'database' => $dbName, 'table' => 'gacl_aro_sections', 'record_key' => 'users', 'operation' => 'INSERT', 'old_state' => null, 'new_state' => 'Users', 'timestamp' => date('Y-m-d H:i:s')];
        }

        // 2. Ensure all 7 canonical ARO groups exist in gacl_aro_groups
        $canonicalGroups = [
            'users' => ['name' => 'OpenEMR Users', 'parent_val' => null],
            'admin' => ['name' => 'Administrators', 'parent_val' => 'users'],
            'clin' => ['name' => 'Clinicians', 'parent_val' => 'users'],
            'doc' => ['name' => 'Physicians', 'parent_val' => 'users'],
            'front' => ['name' => 'Front Office', 'parent_val' => 'users'],
            'back' => ['name' => 'Accounting', 'parent_val' => 'users'],
            'breakglass' => ['name' => 'Emergency Login', 'parent_val' => 'users'],
        ];

        foreach ($canonicalGroups as $gVal => $gMeta) {
            $gExists = (bool) $pdo->query("SELECT 1 FROM gacl_aro_groups WHERE value = " . $pdo->quote($gVal) . " LIMIT 1")->fetchColumn();
            if (!$gExists) {
                $parentId = 0;
                if ($gMeta['parent_val'] !== null) {
                    $parentId = (int) $pdo->query("SELECT id FROM gacl_aro_groups WHERE value = " . $pdo->quote($gMeta['parent_val']))->fetchColumn();
                }
                $nextGrpId = (int) $pdo->query("SELECT COALESCE(MAX(id), 0) + 1 FROM gacl_aro_groups")->fetchColumn();
                $stmtGrp = $pdo->prepare("INSERT INTO gacl_aro_groups (id, parent_id, lft, rgt, name, value) VALUES (?, ?, 0, 0, ?, ?)");
                $stmtGrp->execute([$nextGrpId, $parentId, $gMeta['name'], $gVal]);
                $insertedAcl[] = "gacl_aro_groups: {$gMeta['name']} ({$gVal})";
                $auditLog[] = ['repair_run_id' => $runId, 'subscription_id' => $subscription->id, 'database' => $dbName, 'table' => 'gacl_aro_groups', 'record_key' => $gVal, 'operation' => 'INSERT', 'old_state' => null, 'new_state' => $gMeta['name'], 'timestamp' => date('Y-m-d H:i:s')];
            }
        }

        // 3. Synchronize canonical ACL rules and ACO mappings additively
        $canonicalRules = OpenEmrTenantAuditService::getCanonicalAclRules();
        $stmtInsertMap = $pdo->prepare("INSERT IGNORE INTO gacl_aco_map (acl_id, section_value, value) VALUES (?, ?, ?)");

        foreach ($canonicalRules as $cRule) {
            $gVal = $cRule['group'];
            $retVal = $cRule['return_value'];
            $note = $cRule['note'];

            $groupId = (int) $pdo->query("SELECT id FROM gacl_aro_groups WHERE value = " . $pdo->quote($gVal) . " LIMIT 1")->fetchColumn();
            if (!$groupId) {
                continue;
            }

            // Check if an ACL already exists for this rule
            $stmtFindAcl = $pdo->prepare("
                SELECT a.id 
                FROM gacl_acl a 
                JOIN gacl_aro_groups_map agm ON a.id = agm.acl_id 
                WHERE agm.group_id = ? AND a.return_value = ? AND a.note = ?
                ORDER BY a.id ASC
            ");
            $stmtFindAcl->execute([$groupId, $retVal, $note]);
            $candidateIds = $stmtFindAcl->fetchAll(PDO::FETCH_COLUMN);

            $aclId = null;
            if (count($candidateIds) === 1) {
                if ($cRule['rule_id'] === 'clin_write_med') {
                    $hasAdminDrugs = (bool) $pdo->query("SELECT 1 FROM gacl_aco_map WHERE acl_id = {$candidateIds[0]} AND section_value = 'admin' AND value = 'drugs'")->fetchColumn();
                    if (!$hasAdminDrugs) {
                        $aclId = (int) $candidateIds[0];
                    }
                } elseif ($cRule['rule_id'] === 'clin_write_enc_appt') {
                    $hasAdminDrugs = (bool) $pdo->query("SELECT 1 FROM gacl_aco_map WHERE acl_id = {$candidateIds[0]} AND section_value = 'admin' AND value = 'drugs'")->fetchColumn();
                    if ($hasAdminDrugs) {
                        $aclId = (int) $candidateIds[0];
                    }
                } else {
                    $aclId = (int) $candidateIds[0];
                }
            } elseif (count($candidateIds) > 1) {
                foreach ($candidateIds as $candId) {
                    $hasAdminDrugs = (bool) $pdo->query("SELECT 1 FROM gacl_aco_map WHERE acl_id = {$candId} AND section_value = 'admin' AND value = 'drugs'")->fetchColumn();
                    if ($cRule['rule_id'] === 'clin_write_enc_appt' && $hasAdminDrugs) {
                        $aclId = (int) $candId;
                        break;
                    }
                    if ($cRule['rule_id'] === 'clin_write_med' && !$hasAdminDrugs) {
                        $aclId = (int) $candId;
                        break;
                    }
                }
            }

            if (!$aclId) {
                // Allocate next ACL ID
                $nextAclId = (int) $pdo->query("SELECT COALESCE(MAX(id), 0) + 1 FROM gacl_acl")->fetchColumn();
                $stmtNewAcl = $pdo->prepare("INSERT INTO gacl_acl (id, section_value, return_value, note, updated_date, enabled) VALUES (?, 'system', ?, ?, UNIX_TIMESTAMP(), 1)");
                $stmtNewAcl->execute([$nextAclId, $retVal, $note]);

                // Map ACL to group
                $stmtMapGroup = $pdo->prepare("INSERT IGNORE INTO gacl_aro_groups_map (acl_id, group_id) VALUES (?, ?)");
                $stmtMapGroup->execute([$nextAclId, $groupId]);

                // Update sequence table
                $pdo->exec("UPDATE gacl_acl_seq SET id = GREATEST(id, {$nextAclId})");

                $aclId = $nextAclId;
                $insertedAcl[] = "gacl_acl [{$gVal}]: '{$note}' ({$retVal}) -> ACL ID {$aclId}";
                $auditLog[] = ['repair_run_id' => $runId, 'subscription_id' => $subscription->id, 'database' => $dbName, 'table' => 'gacl_acl', 'record_key' => (string)$aclId, 'operation' => 'INSERT', 'old_state' => null, 'new_state' => $note, 'timestamp' => date('Y-m-d H:i:s')];
            }

            // Ensure all required ACOs exist in gacl_aco_map for this ACL
            foreach ($cRule['acos'] as $sec => $vals) {
                foreach ($vals as $v) {
                    $existsMap = (bool) $pdo->query("SELECT 1 FROM gacl_aco_map WHERE acl_id = {$aclId} AND section_value = " . $pdo->quote($sec) . " AND value = " . $pdo->quote($v) . " LIMIT 1")->fetchColumn();
                    if (!$existsMap) {
                        $stmtInsertMap->execute([$aclId, $sec, $v]);
                        $insertedAcoMappingsCount++;
                        $auditLog[] = ['repair_run_id' => $runId, 'subscription_id' => $subscription->id, 'database' => $dbName, 'table' => 'gacl_aco_map', 'record_key' => "{$aclId}:{$sec}.{$v}", 'operation' => 'INSERT', 'old_state' => null, 'new_state' => "{$sec}.{$v}", 'timestamp' => date('Y-m-d H:i:s')];
                    }
                }
            }
        }

        // 4. Resolve dynamic Administrators group ID and ensure admin user ARO is mapped
        $adminGroupId = $pdo->query("SELECT id FROM gacl_aro_groups WHERE value = 'admin' OR name = 'Administrators' ORDER BY id ASC LIMIT 1")->fetchColumn();
        $adminAroId = $pdo->query("SELECT id FROM gacl_aro WHERE section_value = 'users' AND value = 'admin' LIMIT 1")->fetchColumn();

        if ($adminGroupId && $adminAroId) {
            $stmtMapCheck = $pdo->prepare("SELECT 1 FROM gacl_groups_aro_map WHERE group_id = ? AND aro_id = ? LIMIT 1");
            $stmtMapCheck->execute([$adminGroupId, $adminAroId]);
            if (!$stmtMapCheck->fetchColumn()) {
                $stmtInsertMap = $pdo->prepare("INSERT IGNORE INTO gacl_groups_aro_map (group_id, aro_id) VALUES (?, ?)");
                $stmtInsertMap->execute([$adminGroupId, $adminAroId]);
                $insertedAcl[] = "gacl_groups_aro_map: Admin mapped to group {$adminGroupId}";
                $auditLog[] = ['repair_run_id' => $runId, 'subscription_id' => $subscription->id, 'database' => $dbName, 'table' => 'gacl_groups_aro_map', 'record_key' => "{$adminGroupId}:{$adminAroId}", 'operation' => 'INSERT', 'old_state' => null, 'new_state' => 'mapped', 'timestamp' => date('Y-m-d H:i:s')];
            }
        }

        return [
            'inserted_acl' => $insertedAcl,
            'inserted_aco_mappings_count' => $insertedAcoMappingsCount,
            'admin_group_id_resolved' => (int) $adminGroupId,
            'admin_aro_id_resolved' => (int) $adminAroId,
            'admin_mapped' => true,
        ];
    }

    /**
     * Strict Verification of Data Preservation
     */
    protected function verifyDataPreservation(array $pre, array $post): void
    {
        $preservedCounts = ['users_count', 'patients_count', 'encounters_count', 'facilities_count', 'events_count'];
        foreach ($preservedCounts as $key) {
            if ($pre[$key] !== $post[$key]) {
                throw new RuntimeException("Data preservation invariant violated for {$key}: pre={$pre[$key]}, post={$post[$key]}");
            }
        }
    }

    /**
     * Get isolated tenant PDO
     */
    protected function getTenantPdo(string $dbName): PDO
    {
        $driver = config('database.default', 'mysql');
        $host = config("database.connections.{$driver}.host", '127.0.0.1');
        $port = config("database.connections.{$driver}.port", '3307');
        $user = config("database.connections.{$driver}.username", 'root');
        $pass = config("database.connections.{$driver}.password", 'root');

        return new PDO("mysql:host={$host};port={$port};dbname={$dbName};charset=utf8mb4", $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
    }
}
