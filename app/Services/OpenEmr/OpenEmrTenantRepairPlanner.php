<?php

namespace App\Services\OpenEmr;

use App\Models\Subscription;
use Exception;
use PDO;

class OpenEmrTenantRepairPlanner
{
    protected OpenEmrTenantCompatibilityChecker $checker;
    protected OpenEmrCanonicalGlobalsLoader $globalsLoader;

    public function __construct(
        OpenEmrTenantCompatibilityChecker $checker,
        OpenEmrCanonicalGlobalsLoader $globalsLoader
    ) {
        $this->checker = $checker;
        $this->globalsLoader = $globalsLoader;
    }

    /**
     * Generate a complete, dry-run repair plan for a single managed tenant
     *
     * @param Subscription $subscription
     * @return array
     */
    public function planRepair(Subscription $subscription): array
    {
        // Step 1: Deep compatibility and fingerprint audit
        $compat = $this->checker->checkCompatibility($subscription);

        $plan = [
            'subscription_id' => $subscription->id,
            'doctor_name' => $subscription->doctor_name ?? 'Unknown',
            'tenant_slug' => $subscription->tenant_slug ?? 'unknown',
            'database' => $subscription->openemr_database ?? 'unknown',
            'site_dir' => base_path('oemr/sites/' . ($subscription->tenant_slug ?? '')),
            'compatibility' => $compat,
            'is_safe_to_repair' => false,
            'status' => 'SKIPPED',
            'skip_reason' => null,
            'current_version' => null,
            'current_globals_count' => 0,
            'canonical_globals_count' => 0,
            'missing_globals_count' => 0,
            'version_change_proposed' => null,
            'globals_inserts_proposed' => [],
            'acl_inserts_proposed' => [],
            'admin_mapping_proposed' => null,
            'other_initialization_proposed' => [],
            'existing_data_preserved' => [],
            'backup_specification' => null,
            'risks_and_warnings' => [],
        ];

        // If database does not exist or compatibility check failed
        if (!$compat['is_compatible']) {
            $plan['status'] = 'SKIPPED';
            $plan['skip_reason'] = !empty($compat['errors']) ? implode('; ', $compat['errors']) : 'Incompatible or unverified schema';
            $plan['risks_and_warnings'][] = "Safety guard triggered: " . $plan['skip_reason'];
            return $plan;
        }

        // If already synchronized with canonical 8.3.0
        if ($compat['classification'] === 'CANONICAL_8_3_0_SYNCHRONIZED') {
            $plan['status'] = 'SKIPPED';
            $plan['skip_reason'] = 'Database is already fully synchronized with canonical OpenEMR 8.3.0 schema and revision 541.';
            $plan['current_version'] = '8.3.0 (rev 541, acl 13)';
            return $plan;
        }

        // Must be LEGACY_8_3_0_BASELINE_COMPATIBLE or LEGACY_8_3_0_ACL_UNSTAMPED to proceed to repair calculation
        if (!in_array($compat['classification'], ['LEGACY_8_3_0_BASELINE_COMPATIBLE', 'LEGACY_8_3_0_ACL_UNSTAMPED'])) {
            $plan['status'] = 'SKIPPED';
            $plan['skip_reason'] = "Tenant classification '{$compat['classification']}' is not eligible for baseline repair.";
            return $plan;
        }

        // Deep inspection of tenant DB to calculate exact dry-run changes
        $driver = config('database.default', 'mysql');
        $host = config("database.connections.{$driver}.host", '127.0.0.1');
        $port = config("database.connections.{$driver}.port", '3307');
        $user = config("database.connections.{$driver}.username", 'root');
        $pass = config("database.connections.{$driver}.password", 'root');

        try {
            $pdo = new PDO("mysql:host={$host};port={$port};dbname={$plan['database']};charset=utf8mb4", $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);

            // Version info
            $vRow = $compat['version_row'];
            $plan['current_version'] = "{$vRow['v_major']}.{$vRow['v_minor']}.{$vRow['v_patch']} (rev {$vRow['v_database']}, acl {$vRow['v_acl']})";
            $plan['version_change_proposed'] = [
                'operation' => 'UPDATE',
                'table' => 'version',
                'current' => $plan['current_version'],
                'target' => '8.3.0 (rev 541, acl 13, tag: "")',
                'sql' => "UPDATE version SET v_major = 8, v_minor = 3, v_patch = 0, v_database = 541, v_acl = 13, v_tag = '' WHERE v_database = 0;",
                'rationale' => 'Canonical OpenEMR 8.3.0 Installer::add_version_info() stamping for proven 8.3.0 baseline schema',
            ];

            // Globals audit
            $canonicalInfo = $this->globalsLoader->loadCanonicalGlobals();
            $plan['canonical_globals_count'] = $canonicalInfo['total_discovered'];

            $stmtExistingGlobals = $pdo->query("SELECT gl_name, gl_index, gl_value FROM globals");
            $existingGlobals = [];
            while ($row = $stmtExistingGlobals->fetch(PDO::FETCH_ASSOC)) {
                $existingGlobals[$row['gl_name']] = $row['gl_value'];
            }
            $plan['current_globals_count'] = count($existingGlobals);

            $missingCanonicalKeys = [];
            $preservedGlobals = [];

            foreach ($canonicalInfo['canonical_globals'] as $gk => $gData) {
                if (!array_key_exists($gk, $existingGlobals)) {
                    $missingCanonicalKeys[$gk] = $gData['default'];
                } else {
                    $preservedGlobals[$gk] = [
                        'current_value' => $existingGlobals[$gk],
                        'type' => 'canonical',
                    ];
                }
            }

            foreach ($existingGlobals as $gk => $gv) {
                if (!isset($canonicalInfo['canonical_globals'][$gk])) {
                    $preservedGlobals[$gk] = [
                        'current_value' => $gv,
                        'type' => 'tenant_custom',
                    ];
                }
            }

            $plan['missing_globals_count'] = count($missingCanonicalKeys);
            $plan['globals_inserts_proposed'] = [
                'total_inserts' => count($missingCanonicalKeys),
                'method' => 'INSERT IGNORE (additive only; zero overwrites of existing keys)',
                'sample_keys' => array_slice(array_keys($missingCanonicalKeys), 0, 10),
                'all_missing_keys' => array_keys($missingCanonicalKeys),
            ];

            $plan['existing_data_preserved'] = [
                'total_preserved_globals' => count($preservedGlobals),
                'sample_preserved' => array_slice($preservedGlobals, 0, 8, true),
                'patient_records_preserved' => (int) $pdo->query("SELECT COUNT(*) FROM patient_data")->fetchColumn(),
                'users_preserved' => (int) $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
            ];

            // ACL audit & proposed inserts
            $existingAcoSections = $pdo->query("SELECT value FROM gacl_aco_sections")->fetchAll(PDO::FETCH_COLUMN);
            $existingAroSections = $pdo->query("SELECT value FROM gacl_aro_sections")->fetchAll(PDO::FETCH_COLUMN);
            $existingAcoCount = (int) $pdo->query("SELECT COUNT(*) FROM gacl_aco")->fetchColumn();
            $existingAroGroups = $pdo->query("SELECT value, name FROM gacl_aro_groups")->fetchAll(PDO::FETCH_KEY_PAIR);
            $existingAcoMapCount = (int) $pdo->query("SELECT COUNT(*) FROM gacl_aco_map")->fetchColumn();

            $proposedAcl = [];
            if (!in_array('users', $existingAroSections)) {
                $proposedAcl[] = "INSERT INTO gacl_aro_sections (value, order_value, name, hidden) VALUES ('users', 10, 'Users', 0)";
            }
            if (!array_key_exists('users', $existingAroGroups)) {
                $proposedAcl[] = "INSERT INTO gacl_aro_groups (parent_id, name, value, order_value) VALUES (0, 'OpenEMR Users', 'users', 10)";
            }
            $proposedAcl[] = "Execute canonical official_additional_users.sql (service accounts & permissions)";
            $proposedAcl[] = "Execute canonical on_care_coordination() ACL mappings (Care Coordination ACOs)";
            $proposedAcl[] = "Zero existing ACL records will be deleted or dropped";

            $plan['acl_inserts_proposed'] = [
                'current_aco_count' => $existingAcoCount,
                'current_aco_maps' => $existingAcoMapCount,
                'proposed_actions' => $proposedAcl,
            ];

            // Admin User & ARO mapping
            $adminUserRow = $pdo->query("SELECT id, username, fname, lname FROM users WHERE username = 'admin' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
            $adminAroId = $pdo->query("SELECT id FROM gacl_aro WHERE section_value = 'users' AND value = 'admin' LIMIT 1")->fetchColumn();
            $adminGroupId = $pdo->query("SELECT id FROM gacl_aro_groups WHERE value = 'admin' OR name = 'Administrators' ORDER BY id ASC LIMIT 1")->fetchColumn();

            $adminMapped = false;
            if ($adminAroId && $adminGroupId) {
                $stmtMapCheck = $pdo->prepare("SELECT 1 FROM gacl_groups_aro_map WHERE group_id = ? AND aro_id = ? LIMIT 1");
                $stmtMapCheck->execute([$adminGroupId, $adminAroId]);
                $adminMapped = (bool) $stmtMapCheck->fetchColumn();
            }

            $plan['admin_mapping_proposed'] = [
                'admin_user' => $adminUserRow ? "{$adminUserRow['fname']} {$adminUserRow['lname']} (ID: {$adminUserRow['id']}, username: {$adminUserRow['username']})" : 'Not Found',
                'admin_aro_id' => $adminAroId ?: 'Needs Creation',
                'admin_group_id' => $adminGroupId ?: 'Needs Creation/Resolution',
                'current_mapping_status' => $adminMapped ? 'Already Mapped' : 'Unmapped',
                'proposed_action' => $adminMapped ? 'None (mapping already active)' : "INSERT IGNORE INTO gacl_groups_aro_map (group_id, aro_id) VALUES ({$adminGroupId}, {$adminAroId})",
            ];

            // Other installer data
            $plan['other_initialization_proposed'] = [
                "Verify facility record exists and name aligns with clinic",
                "Verify list_options default_open_tabs contains ('cal', 'msg')",
            ];

            // Backup specification
            $timestamp = date('Ymd_His');
            $plan['backup_specification'] = [
                'backup_file' => storage_path("app/openemr_backups/backup_{$plan['tenant_slug']}_sub{$subscription->id}_{$timestamp}.sql"),
                'subscription_id' => $subscription->id,
                'tenant_slug' => $plan['tenant_slug'],
                'database' => $plan['database'],
                'timestamp' => $timestamp,
                'verification_requirement' => 'mysqldump process must exit with code 0 and output file size must exceed 100,000 bytes prior to repair execution',
            ];

            // Overall safety & verdict
            $plan['is_safe_to_repair'] = true;
            $plan['status'] = 'SAFE TO REPAIR';

        } catch (Exception $e) {
            $plan['status'] = 'SKIPPED';
            $plan['skip_reason'] = "Inspection query exception: " . $e->getMessage();
            $plan['risks_and_warnings'][] = "Database query failed: " . $e->getMessage();
        }

        return $plan;
    }
}
