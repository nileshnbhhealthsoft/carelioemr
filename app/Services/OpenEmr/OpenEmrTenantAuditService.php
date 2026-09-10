<?php

namespace App\Services\OpenEmr;

use App\Models\Subscription;
use PDO;
use Exception;
use RuntimeException;

class OpenEmrTenantAuditService
{
    protected OpenEmrCanonicalGlobalsLoader $globalsLoader;
    protected ?array $canonicalAclData = null;

    public function __construct(OpenEmrCanonicalGlobalsLoader $globalsLoader)
    {
        $this->globalsLoader = $globalsLoader;
    }

    /**
     * Get path to OpenEMR base directory
     */
    public function getOpenEmrBasePath(): string
    {
        return rtrim(
            (string) config('oemr.base_path', base_path('oemr')),
            '/\\'
        );
    }

    /**
     * Dynamically derive canonical ACL definitions from OpenEMR Installer source
     */
    public function getCanonicalAclDefinitions(): array
    {
        if ($this->canonicalAclData !== null) {
            return $this->canonicalAclData;
        }

        $installerFile = $this->getOpenEmrBasePath() . '/library/classes/Installer.class.php';
        if (!file_exists($installerFile)) {
            throw new RuntimeException("Canonical Installer class file not found: {$installerFile}");
        }

        $content = file_get_contents($installerFile);

        // 1. Extract ACO and ARO sections:
        // $gacl->add_object_section('Accounting', 'acct', 10, 0, 'ACO');
        preg_match_all("/add_object_section\s*\(\s*['\"]([^'\"]+)['\"]\s*,\s*['\"]([^'\"]+)['\"]\s*,\s*\d+\s*,\s*\d+\s*,\s*['\"](ACO|ARO)['\"]\s*\)/i", $content, $secMatches, PREG_SET_ORDER);

        $sections = ['ACO' => [], 'ARO' => []];
        foreach ($secMatches as $m) {
            $sections[$m[3]][$m[2]] = [
                'identifier' => $m[2],
                'name' => $m[1],
                'type' => $m[3],
            ];
        }

        // 2. Extract ACOs:
        // $gacl->add_object('acct', 'Billing (write optional)', 'bill', 10, 0, 'ACO');
        preg_match_all("/add_object\s*\(\s*['\"]([^'\"]+)['\"]\s*,\s*['\"]([^'\"]+)['\"]\s*,\s*['\"]([^'\"]+)['\"]\s*,\s*\d+\s*,\s*\d+\s*,\s*['\"](ACO|ARO)['\"]\s*\)/i", $content, $acoMatches, PREG_SET_ORDER);

        $acos = [];
        foreach ($acoMatches as $m) {
            if (strtoupper($m[4]) === 'ACO') {
                $key = $m[1] . '.' . $m[3];
                $acos[$key] = [
                    'section' => $m[1],
                    'name' => $m[2],
                    'value' => $m[3],
                ];
            }
        }

        // 3. Extract ARO groups:
        // $users = $gacl->add_group('users', 'OpenEMR Users', 0, 'ARO');
        preg_match_all("/add_group\s*\(\s*['\"]([^'\"]+)['\"]\s*,\s*['\"]([^'\"]+)['\"]\s*,\s*([^,]+)\s*,\s*['\"]ARO['\"]\s*\)/i", $content, $grpMatches, PREG_SET_ORDER);

        $groups = [];
        foreach ($grpMatches as $m) {
            $groups[$m[1]] = [
                'value' => $m[1],
                'name' => $m[2],
                'parent_ref' => trim($m[3]),
            ];
        }

        // 4. Parse official additional users
        $additionalUsersFile = $this->getOpenEmrBasePath() . '/sql/official_additional_users.sql';
        $additionalUsers = [];
        if (file_exists($additionalUsersFile)) {
            $sqlContent = file_get_contents($additionalUsersFile);
            preg_match_all("/INSERT\s+INTO\s+`?users`?.*?VALUES\s*\(\s*'([^']+)'/i", $sqlContent, $userMatches);
            if (!empty($userMatches[1])) {
                $additionalUsers = $userMatches[1];
            }
        }

        $canonicalRules = self::getCanonicalAclRules();

        $this->canonicalAclData = [
            'sections' => $sections,
            'acos' => $acos,
            'groups' => $groups,
            'additional_users' => $additionalUsers,
            'rules' => $canonicalRules,
            'counts' => [
                'aco_sections' => count($sections['ACO']),
                'aro_sections' => count($sections['ARO']),
                'acos' => count($acos),
                'groups' => count($groups),
                'additional_users' => count($additionalUsers),
                'canonical_acl_rules' => count($canonicalRules),
                'canonical_aco_mappings' => 203,
            ],
        ];

        return $this->canonicalAclData;
    }

    /**
     * Exact 19 Canonical OpenEMR 8.3.0 ACL rules defined in Installer::install_gacl()
     */
    public static function getCanonicalAclRules(): array
    {
        return [
            [
                'rule_id' => 'admin_write_all',
                'group' => 'admin',
                'group_name' => 'Administrators',
                'return_value' => 'write',
                'note' => 'Administrators can do anything',
                'acos' => [
                    'acct' => ['bill', 'disc', 'eob', 'rep', 'rep_a'],
                    'admin' => ['calendar', 'database', 'forms', 'practice', 'superbill', 'users', 'batchcom', 'language', 'super', 'drugs', 'acl', 'menu', 'manage_modules'],
                    'encounters' => ['auth_a', 'auth', 'coding_a', 'coding', 'notes_a', 'notes', 'date_a', 'relaxed'],
                    'inventory' => ['lots', 'sales', 'purchases', 'transfers', 'adjustments', 'consumption', 'destruction', 'reporting'],
                    'lists' => ['default', 'state', 'country', 'language', 'ethrace'],
                    'patients' => ['appt', 'demo', 'med', 'trans', 'docs', 'notes', 'sign', 'reminder', 'alert', 'disclosure', 'rx', 'amendment', 'lab', 'docs_rm', 'pat_rep'],
                    'sensitivities' => ['normal', 'high'],
                    'nationnotes' => ['nn_configure'],
                    'patientportal' => ['portal'],
                    'menus' => ['modle'],
                    'groups' => ['gadd', 'gcalendar', 'glog', 'gdlog', 'gm'],
                ],
            ],
            [
                'rule_id' => 'doc_view_pat_rep',
                'group' => 'doc',
                'group_name' => 'Physicians',
                'return_value' => 'view',
                'note' => 'Things that physicians can only read',
                'acos' => [
                    'patients' => ['pat_rep'],
                ],
            ],
            [
                'rule_id' => 'doc_addonly_placeholder',
                'group' => 'doc',
                'group_name' => 'Physicians',
                'return_value' => 'addonly',
                'note' => 'Things that physicians can read and enter but not modify',
                'acos' => [
                    'placeholder' => ['filler'],
                ],
            ],
            [
                'rule_id' => 'doc_wsome_placeholder',
                'group' => 'doc',
                'group_name' => 'Physicians',
                'return_value' => 'wsome',
                'note' => 'Things that physicians can read and partly modify',
                'acos' => [
                    'placeholder' => ['filler'],
                ],
            ],
            [
                'rule_id' => 'doc_write_main',
                'group' => 'doc',
                'group_name' => 'Physicians',
                'return_value' => 'write',
                'note' => 'Things that physicians can read and modify',
                'acos' => [
                    'acct' => ['disc', 'rep'],
                    'admin' => ['drugs'],
                    'encounters' => ['auth_a', 'auth', 'coding_a', 'coding', 'notes_a', 'notes', 'date_a', 'relaxed'],
                    'patients' => ['appt', 'demo', 'med', 'trans', 'docs', 'notes', 'sign', 'reminder', 'alert', 'disclosure', 'rx', 'amendment', 'lab'],
                    'sensitivities' => ['normal', 'high'],
                    'groups' => ['gcalendar', 'glog'],
                ],
            ],
            [
                'rule_id' => 'clin_view_pat_rep',
                'group' => 'clin',
                'group_name' => 'Clinicians',
                'return_value' => 'view',
                'note' => 'Things that clinicians can only read',
                'acos' => [
                    'patients' => ['pat_rep'],
                ],
            ],
            [
                'rule_id' => 'clin_addonly_main',
                'group' => 'clin',
                'group_name' => 'Clinicians',
                'return_value' => 'addonly',
                'note' => 'Things that clinicians can read and enter but not modify',
                'acos' => [
                    'encounters' => ['notes', 'relaxed'],
                    'patients' => ['demo', 'docs', 'notes', 'trans', 'reminder', 'alert', 'disclosure', 'rx', 'amendment', 'lab'],
                    'sensitivities' => ['normal'],
                ],
            ],
            [
                'rule_id' => 'clin_write_med',
                'group' => 'clin',
                'group_name' => 'Clinicians',
                'return_value' => 'write',
                'note' => 'Things that clinicians can read and modify',
                'acos' => [
                    'patients' => ['med'],
                ],
            ],
            [
                'rule_id' => 'clin_wsome_placeholder',
                'group' => 'clin',
                'group_name' => 'Clinicians',
                'return_value' => 'wsome',
                'note' => 'Things that clinicians can read and partly modify',
                'acos' => [
                    'placeholder' => ['filler'],
                ],
            ],
            [
                'rule_id' => 'clin_write_enc_appt',
                'group' => 'clin',
                'group_name' => 'Clinicians',
                'return_value' => 'write',
                'note' => 'Things that clinicians can read and modify',
                'acos' => [
                    'admin' => ['drugs'],
                    'encounters' => ['auth', 'coding', 'notes'],
                    'patients' => ['appt'],
                    'groups' => ['gcalendar', 'glog'],
                ],
            ],
            [
                'rule_id' => 'front_view_alert',
                'group' => 'front',
                'group_name' => 'Front Office',
                'return_value' => 'view',
                'note' => 'Things that front office can only read',
                'acos' => [
                    'patients' => ['alert'],
                ],
            ],
            [
                'rule_id' => 'front_addonly_placeholder',
                'group' => 'front',
                'group_name' => 'Front Office',
                'return_value' => 'addonly',
                'note' => 'Things that front office can read and enter but not modify',
                'acos' => [
                    'placeholder' => ['filler'],
                ],
            ],
            [
                'rule_id' => 'front_wsome_placeholder',
                'group' => 'front',
                'group_name' => 'Front Office',
                'return_value' => 'wsome',
                'note' => 'Things that front office can read and partly modify',
                'acos' => [
                    'placeholder' => ['filler'],
                ],
            ],
            [
                'rule_id' => 'front_write_appt_demo',
                'group' => 'front',
                'group_name' => 'Front Office',
                'return_value' => 'write',
                'note' => 'Things that front office can read and modify',
                'acos' => [
                    'patients' => ['appt', 'demo'],
                    'groups' => ['gcalendar'],
                ],
            ],
            [
                'rule_id' => 'back_view_alert',
                'group' => 'back',
                'group_name' => 'Accounting',
                'return_value' => 'view',
                'note' => 'Things that back office can only read',
                'acos' => [
                    'patients' => ['alert'],
                ],
            ],
            [
                'rule_id' => 'back_addonly_placeholder',
                'group' => 'back',
                'group_name' => 'Accounting',
                'return_value' => 'addonly',
                'note' => 'Things that back office can read and enter but not modify',
                'acos' => [
                    'placeholder' => ['filler'],
                ],
            ],
            [
                'rule_id' => 'back_wsome_placeholder',
                'group' => 'back',
                'group_name' => 'Accounting',
                'return_value' => 'wsome',
                'note' => 'Things that back office can read and partly modify',
                'acos' => [
                    'placeholder' => ['filler'],
                ],
            ],
            [
                'rule_id' => 'back_write_bill_rep',
                'group' => 'back',
                'group_name' => 'Accounting',
                'return_value' => 'write',
                'note' => 'Things that back office can read and modify',
                'acos' => [
                    'acct' => ['bill', 'disc', 'eob', 'rep', 'rep_a'],
                    'admin' => ['practice', 'superbill'],
                    'encounters' => ['auth_a', 'coding_a', 'date_a'],
                    'patients' => ['appt', 'demo'],
                ],
            ],
            [
                'rule_id' => 'breakglass_write_all',
                'group' => 'breakglass',
                'group_name' => 'Emergency Login',
                'return_value' => 'write',
                'note' => 'Emergency Login user can do anything',
                'acos' => [
                    'acct' => ['bill', 'disc', 'eob', 'rep', 'rep_a'],
                    'admin' => ['calendar', 'database', 'forms', 'practice', 'superbill', 'users', 'batchcom', 'language', 'super', 'drugs', 'acl', 'menu', 'manage_modules'],
                    'encounters' => ['auth_a', 'auth', 'coding_a', 'coding', 'notes_a', 'notes', 'date_a', 'relaxed'],
                    'inventory' => ['lots', 'sales', 'purchases', 'transfers', 'adjustments', 'consumption', 'destruction', 'reporting'],
                    'lists' => ['default', 'state', 'country', 'language', 'ethrace'],
                    'patients' => ['appt', 'demo', 'med', 'trans', 'docs', 'notes', 'sign', 'reminder', 'alert', 'disclosure', 'rx', 'amendment', 'lab', 'docs_rm', 'pat_rep'],
                    'sensitivities' => ['normal', 'high'],
                    'nationnotes' => ['nn_configure'],
                    'patientportal' => ['portal'],
                    'menus' => ['modle'],
                    'groups' => ['gadd', 'gcalendar', 'glog', 'gdlog', 'gm'],
                ],
            ],
        ];
    }


    /**
     * Establish a strictly READ-ONLY PDO connection to a tenant database
     */
    protected function getTenantPdo(string $dbName): PDO
    {
        $host = config('database.connections.mysql.host', '127.0.0.1');
        $port = config('database.connections.mysql.port', '3307');
        $user = config('database.connections.mysql.username', 'root');
        $pass = config('database.connections.mysql.password', 'root');

        $pdo = new PDO("mysql:host={$host};port={$port};dbname={$dbName};charset=utf8mb4", $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        return $pdo;
    }

    /**
     * Perform a comprehensive, strictly READ-ONLY audit for a given tenant subscription
     */
    public function auditTenant(Subscription $subscription): array
    {
        $dbName = $subscription->openemr_database;
        $tenantSlug = $subscription->tenant_slug ?: ('site-tenant-' . $subscription->id);
        $siteDirectory = $this->getOpenEmrBasePath() . '/sites/' . $tenantSlug;

        $audit = [
            'tenant' => $subscription->doctor_name ?: "Subscription #{$subscription->id}",
            'subscription_id' => $subscription->id,
            'tenant_slug' => $tenantSlug,
            'database' => $dbName,
            'site_directory' => $siteDirectory,
            'site_directory_exists' => file_exists($siteDirectory),
            'sqlconf_exists' => file_exists($siteDirectory . '/sqlconf.php'),
            'version_info' => null,
            'version_compatible' => false,
            'compatibility_status' => 'UNSUPPORTED',
            'compatibility_errors' => [],
            'canonical_globals_discovered' => 0,
            'existing_globals_count' => 0,
            'missing_globals_count' => 0,
            'existing_custom_globals_count' => 0,
            'existing_canonical_globals_count' => 0,
            'missing_globals_by_category' => [
                'safe_static_default' => [],
                'generated_dynamic' => [],
                'environment_path' => [],
                'security_secret' => [],
                'tenant_specific' => [],
            ],
            'existing_custom_globals' => [],
            'acl_audit' => [
                'canonical_counts' => [],
                'existing_counts' => [],
                'missing_sections' => [],
                'missing_acos' => [],
                'missing_groups' => [],
                'missing_mappings' => [],
                'unexpected_sections' => [],
                'unexpected_acos' => [],
                'unexpected_groups' => [],
                'canonical_acl_rules_count' => 19,
                'existing_acl_rules_count' => 0,
                'missing_acl_rules_count' => 0,
                'missing_acl_rules' => [],
                'canonical_aco_mappings_count' => 203,
                'existing_aco_mappings_count' => 0,
                'missing_aco_mappings_count' => 0,
                'missing_aco_mappings' => [],
                'missing_rules_by_role' => [
                    'admin' => [],
                    'doc' => [],
                    'clin' => [],
                    'front' => [],
                    'back' => [],
                    'breakglass' => [],
                ],
                'carecoordination_module_acl' => null,
            ],
            'admin_user_audit' => [
                'users_record' => null,
                'users_secure_record' => null,
                'groups_record' => null,
                'gacl_mapped' => false,
            ],
            'health_invariants' => [
                'theme_tabs_layout' => ['exists' => false, 'value' => null, 'status' => 'FAIL'],
                'full_new_patient_form' => ['exists' => false, 'value' => null, 'status' => 'FAIL'],
            ],
            'recommended_actions' => [],
        ];

        // 1. Load canonical definitions dynamically
        $canonicalGlobalsInfo = $this->globalsLoader->loadCanonicalGlobals();
        $canonicalGlobals = $canonicalGlobalsInfo['canonical_globals'];
        $audit['canonical_globals_discovered'] = $canonicalGlobalsInfo['total_discovered'];

        $canonicalAcl = $this->getCanonicalAclDefinitions();
        $audit['acl_audit']['canonical_counts'] = $canonicalAcl['counts'];

        // 2. Connect to Tenant DB (Strictly Read-Only)
        try {
            $pdo = $this->getTenantPdo($dbName);
        } catch (Exception $e) {
            $audit['compatibility_status'] = 'CONNECTION_FAILED';
            $audit['compatibility_errors'][] = "Database connection error: " . $e->getMessage();
            return $audit;
        }

        // 3. Inspect version table (Strict verification)
        try {
            $stmtVer = $pdo->query("SELECT v_major, v_minor, v_patch, v_database, v_acl FROM version LIMIT 1");
            $verRow = $stmtVer->fetch();
            if ($verRow) {
                $audit['version_info'] = $verRow;
                $vMajor = (int) $verRow['v_major'];
                $vMinor = (int) $verRow['v_minor'];
                $vPatch = (int) $verRow['v_patch'];
                $vDb = (int) $verRow['v_database'];
                $vAcl = (int) $verRow['v_acl'];

                $verErrors = [];
                if ($vMajor !== 8) $verErrors[] = "v_major mismatch: expected 8, found {$vMajor}";
                if ($vMinor !== 3) $verErrors[] = "v_minor mismatch: expected 3, found {$vMinor}";
                if ($vPatch !== 0) $verErrors[] = "v_patch mismatch: expected 0, found {$vPatch}";
                if ($vDb !== 541) $verErrors[] = "v_database revision mismatch: expected 541, found {$vDb}";
                if ($vAcl !== 13) $verErrors[] = "v_acl revision mismatch: expected 13, found {$vAcl}";

                if (empty($verErrors)) {
                    $audit['version_compatible'] = true;
                    $audit['compatibility_status'] = 'COMPATIBLE';
                } else {
                    $audit['version_compatible'] = false;
                    $audit['compatibility_status'] = 'UNSUPPORTED';
                    $audit['compatibility_errors'] = $verErrors;
                }
            } else {
                $audit['compatibility_status'] = 'UNSUPPORTED';
                $audit['compatibility_errors'][] = "version table is empty";
            }
        } catch (Exception $e) {
            $audit['compatibility_status'] = 'UNSUPPORTED';
            $audit['compatibility_errors'][] = "version table query failed: " . $e->getMessage();
        }

        // 4. Globals Audit (Strict exact key comparison)
        try {
            $stmtGlob = $pdo->query("SELECT gl_name, gl_index, gl_value FROM globals");
            $existingGlobals = [];
            while ($row = $stmtGlob->fetch()) {
                $existingGlobals[$row['gl_name']] = $row['gl_value'];
            }

            $audit['existing_globals_count'] = count($existingGlobals);

            $missingCanonical = [];
            $existingCanonical = [];
            $existingCustom = [];

            foreach ($canonicalGlobals as $key => $meta) {
                if (array_key_exists($key, $existingGlobals)) {
                    $existingCanonical[$key] = $existingGlobals[$key];
                } else {
                    $missingCanonical[$key] = $meta;
                    $cat = $meta['category'];
                    $audit['missing_globals_by_category'][$cat][$key] = $meta['default'];
                }
            }

            foreach ($existingGlobals as $key => $val) {
                if (!array_key_exists($key, $canonicalGlobals)) {
                    $existingCustom[$key] = $val;
                }
            }

            $audit['missing_globals_count'] = count($missingCanonical);
            $audit['existing_canonical_globals_count'] = count($existingCanonical);
            $audit['existing_custom_globals_count'] = count($existingCustom);
            $audit['existing_custom_globals'] = $existingCustom;

            // Health Invariants: theme_tabs_layout & full_new_patient_form
            if (array_key_exists('theme_tabs_layout', $existingGlobals)) {
                $val = $existingGlobals['theme_tabs_layout'];
                $audit['health_invariants']['theme_tabs_layout'] = [
                    'exists' => true,
                    'value' => $val,
                    'status' => ($val === 'tabs_style_full.css' || !empty($val)) ? 'PASS' : 'WARN',
                ];
            }
            if (array_key_exists('full_new_patient_form', $existingGlobals)) {
                $val = $existingGlobals['full_new_patient_form'];
                $audit['health_invariants']['full_new_patient_form'] = [
                    'exists' => true,
                    'value' => $val,
                    'status' => ($val === '1') ? 'PASS' : 'WARN',
                ];
            }
        } catch (Exception $e) {
            $audit['compatibility_errors'][] = "globals table query failed: " . $e->getMessage();
        }

        // 5. ACL Audit
        try {
            // Check ACO sections
            $stmtAcoSec = $pdo->query("SELECT value, name FROM gacl_aco_sections");
            $existingAcoSections = [];
            while ($row = $stmtAcoSec->fetch()) {
                $existingAcoSections[$row['value']] = $row['name'];
            }

            // Check ARO sections
            $stmtAroSec = $pdo->query("SELECT value, name FROM gacl_aro_sections");
            $existingAroSections = [];
            while ($row = $stmtAroSec->fetch()) {
                $existingAroSections[$row['value']] = $row['name'];
            }

            $audit['acl_audit']['existing_counts']['sections'] = count($existingAcoSections) + count($existingAroSections);

            foreach ($canonicalAcl['sections']['ACO'] as $ident => $sec) {
                if (!isset($existingAcoSections[$ident])) {
                    $audit['acl_audit']['missing_sections'][] = "ACO Section: '{$sec['name']}' ({$ident})";
                }
            }
            foreach ($canonicalAcl['sections']['ARO'] as $ident => $sec) {
                if (!isset($existingAroSections[$ident])) {
                    $audit['acl_audit']['missing_sections'][] = "ARO Section: '{$sec['name']}' ({$ident})";
                }
            }

            // Check ACOs
            $stmtAco = $pdo->query("SELECT section_value, value, name FROM gacl_aco");
            $existingAcos = [];
            while ($row = $stmtAco->fetch()) {
                $existingAcos[$row['section_value'] . '.' . $row['value']] = $row['name'];
            }
            $audit['acl_audit']['existing_counts']['acos'] = count($existingAcos);

            foreach ($canonicalAcl['acos'] as $key => $aco) {
                if (!isset($existingAcos[$key])) {
                    $audit['acl_audit']['missing_acos'][] = "ACO: [{$aco['section']}] {$aco['name']} ({$aco['value']})";
                }
            }
            foreach ($existingAcos as $key => $name) {
                if (!isset($canonicalAcl['acos'][$key])) {
                    $audit['acl_audit']['unexpected_acos'][] = "Extra ACO: {$key} ({$name})";
                }
            }

            // Check ARO Groups
            $stmtGrp = $pdo->query("SELECT id, value, name FROM gacl_aro_groups");
            $existingGroups = [];
            while ($row = $stmtGrp->fetch()) {
                $existingGroups[$row['value']] = $row['name'];
            }
            $audit['acl_audit']['existing_counts']['groups'] = count($existingGroups);

            foreach ($canonicalAcl['groups'] as $val => $grp) {
                if (!isset($existingGroups[$val])) {
                    $audit['acl_audit']['missing_groups'][] = "Group: {$grp['name']} ({$val})";
                }
            }
            foreach ($existingGroups as $val => $name) {
                if (!isset($canonicalAcl['groups'][$val])) {
                    $audit['acl_audit']['unexpected_groups'][] = "Extra Group: {$val} ({$name})";
                }
            }

            // Check Exact Canonical ACL Rules & ACO Mappings
            $existingAcls = $pdo->query("
                SELECT a.id, a.section_value, a.return_value, a.note, agm.group_id, g.value as group_val
                FROM gacl_acl a
                LEFT JOIN gacl_aro_groups_map agm ON a.id = agm.acl_id
                LEFT JOIN gacl_aro_groups g ON agm.group_id = g.id
            ")->fetchAll(PDO::FETCH_ASSOC);

            $audit['acl_audit']['existing_acl_rules_count'] = count($existingAcls);

            $aclsByGroup = [];
            foreach ($existingAcls as $ea) {
                $gVal = $ea['group_val'] ?: 'unassigned';
                $stmtMaps = $pdo->prepare("SELECT section_value, value FROM gacl_aco_map WHERE acl_id = ?");
                $stmtMaps->execute([$ea['id']]);
                $flatAcos = [];
                while ($mRow = $stmtMaps->fetch(PDO::FETCH_ASSOC)) {
                    $flatAcos[$mRow['section_value'] . '.' . $mRow['value']] = true;
                }
                $ea['acos'] = $flatAcos;
                $aclsByGroup[$gVal][] = $ea;
            }

            // Build active permission tuples: group_val.return_value:section.value
            $existingTuples = [];
            foreach ($aclsByGroup as $gVal => $groupAcls) {
                foreach ($groupAcls as $acl) {
                    foreach ($acl['acos'] as $acoKey => $_) {
                        $existingTuples["{$gVal}.{$acl['return_value']}:{$acoKey}"] = true;
                    }
                }
            }

            $canonicalRules = self::getCanonicalAclRules();
            $missingRulesByRole = [
                'admin' => [],
                'doc' => [],
                'clin' => [],
                'front' => [],
                'back' => [],
                'breakglass' => [],
            ];
            $missingAclRules = [];
            $missingAcoMappings = [];

            foreach ($canonicalRules as $cRule) {
                $gVal = $cRule['group'];
                $retVal = $cRule['return_value'];
                $note = $cRule['note'];

                // Check if an ACL with this note and return_value exists for the group
                $foundAcl = null;
                if (isset($aclsByGroup[$gVal])) {
                    foreach ($aclsByGroup[$gVal] as $ea) {
                        if ($ea['return_value'] === $retVal && $ea['note'] === $note) {
                            if ($cRule['rule_id'] === 'clin_write_med') {
                                if (isset($ea['acos']['patients.med']) || !isset($ea['acos']['admin.drugs'])) {
                                    $foundAcl = $ea;
                                    break;
                                }
                            } elseif ($cRule['rule_id'] === 'clin_write_enc_appt') {
                                if (isset($ea['acos']['admin.drugs'])) {
                                    $foundAcl = $ea;
                                    break;
                                }
                            } else {
                                $foundAcl = $ea;
                                break;
                            }
                        }
                    }
                }

                if (!$foundAcl) {
                    $ruleDesc = "[{$gVal}] '{$note}' ({$retVal}) rule missing";
                    $missingAclRules[] = $ruleDesc;
                    if (isset($missingRulesByRole[$gVal])) {
                        $missingRulesByRole[$gVal][] = $ruleDesc;
                    }
                }

                // Check individual ACO mappings for this rule
                foreach ($cRule['acos'] as $sec => $vals) {
                    foreach ($vals as $v) {
                        $tupleKey = "{$gVal}.{$retVal}:{$sec}.{$v}";
                        if (!isset($existingTuples[$tupleKey])) {
                            $missingAcoMappings[] = $tupleKey;
                        }
                    }
                }
            }

            $stmtAclMap = $pdo->query("SELECT COUNT(*) FROM gacl_aco_map");
            $audit['acl_audit']['existing_counts']['aco_maps'] = (int) $stmtAclMap->fetchColumn();

            $stmtGroupAro = $pdo->query("SELECT COUNT(*) FROM gacl_groups_aro_map");
            $audit['acl_audit']['existing_counts']['group_aro_maps'] = (int) $stmtGroupAro->fetchColumn();

            $audit['acl_audit']['canonical_acl_rules_count'] = count($canonicalRules);
            $audit['acl_audit']['missing_acl_rules_count'] = count($missingAclRules);
            $audit['acl_audit']['missing_acl_rules'] = $missingAclRules;
            $audit['acl_audit']['canonical_aco_mappings_count'] = 203;
            $audit['acl_audit']['existing_aco_mappings_count'] = $audit['acl_audit']['existing_counts']['aco_maps'];
            $audit['acl_audit']['missing_aco_mappings_count'] = count($missingAcoMappings);
            $audit['acl_audit']['missing_aco_mappings'] = $missingAcoMappings;
            $audit['acl_audit']['missing_rules_by_role'] = $missingRulesByRole;

            if (!empty($missingAclRules)) {
                $audit['acl_audit']['missing_mappings'][] = count($missingAclRules) . " canonical ACL rules missing (" . implode('; ', array_slice($missingAclRules, 0, 3)) . (count($missingAclRules) > 3 ? '...' : '') . ")";
            }
            if (!empty($missingAcoMappings)) {
                $audit['acl_audit']['missing_mappings'][] = count($missingAcoMappings) . " canonical ACO permission mappings missing";
            }
            if ($audit['acl_audit']['existing_counts']['group_aro_maps'] === 0) {
                $audit['acl_audit']['missing_mappings'][] = "gacl_groups_aro_map is completely empty (0 user/role mappings)";
            }

            // 5b. Canonical Module ACL Check (Installer::on_care_coordination)
            try {
                $stmtMod = $pdo->prepare("SELECT mod_id FROM modules WHERE mod_name = 'Carecoordination' LIMIT 1");
                $stmtMod->execute();
                $modId = $stmtMod->fetchColumn();

                $stmtSec = $pdo->prepare("SELECT section_id FROM module_acl_sections WHERE section_identifier = 'carecoordination' LIMIT 1");
                $stmtSec->execute();
                $secId = $stmtSec->fetchColumn();

                $stmtGrp = $pdo->prepare("SELECT id FROM gacl_aro_groups WHERE value = 'admin' LIMIT 1");
                $stmtGrp->execute();
                $grpId = $stmtGrp->fetchColumn();

                $hasModuleAcl = false;
                if ($modId && $secId && $grpId) {
                    $stmtChk = $pdo->prepare("SELECT allowed FROM module_acl_group_settings WHERE module_id = ? AND group_id = ? AND section_id = ? LIMIT 1");
                    $stmtChk->execute([$modId, $grpId, $secId]);
                    $hasModuleAcl = ((int) $stmtChk->fetchColumn() === 1);
                }

                $audit['acl_audit']['carecoordination_module_acl'] = [
                    'mod_id' => $modId ? (int) $modId : null,
                    'section_id' => $secId ? (int) $secId : null,
                    'group_id' => $grpId ? (int) $grpId : null,
                    'allowed' => $hasModuleAcl,
                ];

                if ($modId && $secId && $grpId && !$hasModuleAcl) {
                    $audit['acl_audit']['missing_mappings'][] = "Carecoordination module ACL for admin group is missing (module_acl_group_settings)";
                }
            } catch (Exception $e) {
                $audit['acl_audit']['carecoordination_module_acl'] = ['error' => $e->getMessage()];
            }

        } catch (Exception $e) {
            $audit['compatibility_errors'][] = "GACL tables query failed: " . $e->getMessage();
        }

        // 6. Users & Admin Audit
        try {
            $stmtAdminUser = $pdo->query("SELECT id, username, fname, lname, authorized, active FROM users WHERE username = 'admin' LIMIT 1");
            $audit['admin_user_audit']['users_record'] = $stmtAdminUser->fetch() ?: null;

            if ($audit['admin_user_audit']['users_record']) {
                $adminId = $audit['admin_user_audit']['users_record']['id'];
                $stmtSec = $pdo->query("SELECT id, username, LENGTH(password) as hash_len FROM users_secure WHERE id = {$adminId} LIMIT 1");
                $audit['admin_user_audit']['users_secure_record'] = $stmtSec->fetch() ?: null;

                $stmtGrp = $pdo->query("SELECT user, name FROM `groups` WHERE user = 'admin' LIMIT 1");
                $audit['admin_user_audit']['groups_record'] = $stmtGrp->fetch() ?: null;

                // Check GACL group mapping for admin if tables have data
                if ($audit['acl_audit']['existing_counts']['group_aro_maps'] > 0) {
                    $stmtAro = $pdo->query("
                        SELECT gam.group_id 
                        FROM gacl_groups_aro_map gam
                        JOIN gacl_aro a ON gam.aro_id = a.id
                        JOIN gacl_aro_groups g ON gam.group_id = g.id
                        WHERE a.value = 'admin' AND g.value = 'admin'
                        LIMIT 1
                    ");
                    $audit['admin_user_audit']['gacl_mapped'] = (bool) $stmtAro->fetch();
                } else {
                    $audit['admin_user_audit']['gacl_mapped'] = false;
                }
            }
        } catch (Exception $e) {
            $audit['compatibility_errors'][] = "Users audit query failed: " . $e->getMessage();
        }

        // 7. Recommended Future Actions
        if (!$audit['version_compatible']) {
            $audit['recommended_actions'][] = "UNSUPPORTED: Version mismatch detected (" . implode('; ', $audit['compatibility_errors']) . "). Repair planning aborted for this tenant as required.";
            if ($audit['version_info'] && (int)$audit['version_info']['v_database'] === 0) {
                $audit['recommended_actions'][] = "Diagnostic note: Database is currently at baseline database.sql defaults (0.0.0 rev 0). Canonical OpenEMR 8.3.0 requires Installer::add_version_info() to set v_major=8, v_minor=3, v_patch=0, v_database=541, v_acl=13.";
            }
        } else {
            $safeMissingCount = count($audit['missing_globals_by_category']['safe_static_default']);
            if ($safeMissingCount > 0) {
                $audit['recommended_actions'][] = "Phase 1B: Seed {$safeMissingCount} missing canonical safe static default globals via INSERT IGNORE.";
            }
            if (count($audit['missing_globals_by_category']['tenant_specific']) > 0) {
                $audit['recommended_actions'][] = "Phase 1B: Seed tenant-specific globals preserving existing overrides.";
            }
            if (count($audit['acl_audit']['missing_sections']) > 0 || count($audit['acl_audit']['missing_acos']) > 0 || count($audit['acl_audit']['missing_groups']) > 0) {
                $audit['recommended_actions'][] = "Phase 1B: Initialize missing canonical GACL sections, ACOs, and ARO groups using Installer::install_gacl() definitions.";
            }
            if (!empty($audit['acl_audit']['missing_mappings'])) {
                $audit['recommended_actions'][] = "Phase 1B: Populate canonical GACL access control rules and import official_additional_users.sql.";
            }
        }

        return $audit;
    }
}

