<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use App\Services\OpenEmr\OpenEmrCanonicalGlobalsLoader;
use App\Services\OpenEmr\OpenEmrTenantAuditService;
use Illuminate\Console\Command;

class OpenEmrAuditTenantsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'openemr:audit-tenants 
                            {--tenant= : Specific tenant slug, database name, or subscription ID} 
                            {--all : Audit all managed tenant databases}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Perform a strictly READ-ONLY canonical audit of OpenEMR tenant databases against OpenEMR 8.3.0 installer standards';

    /**
     * Execute the console command.
     */
    public function handle(OpenEmrCanonicalGlobalsLoader $loader, OpenEmrTenantAuditService $auditService): int
    {
        $this->info("================================================================================");
        $this->info("      CANONICAL OPENEMR 8.3.0 MULTI-TENANT READ-ONLY AUDIT LAYER (PHASE 1A)      ");
        $this->info("================================================================================");

        $tenantOption = $this->option('tenant');
        $allOption = $this->option('all');

        // Discover tenants strictly via Laravel Subscription models
        $query = Subscription::query()->whereNotNull('openemr_database');

        if ($tenantOption) {
            $query->where(function ($q) use ($tenantOption) {
                $q->where('id', $tenantOption)
                  ->orWhere('tenant_slug', $tenantOption)
                  ->orWhere('openemr_database', $tenantOption)
                  ->orWhere('doctor_name', 'like', "%{$tenantOption}%");
            });
        }

        $subscriptions = $query->get();

        if ($subscriptions->isEmpty()) {
            $this->warn("No matching tenant subscriptions found in database.");
            return 0;
        }

        $this->line("Discovered {$subscriptions->count()} managed tenant subscription(s) in Laravel database.\n");

        foreach ($subscriptions as $subscription) {
            $this->renderTenantAudit($auditService->auditTenant($subscription));
        }

        $this->info("================================================================================");
        $this->info("READ-ONLY AUDIT COMPLETE");
        $this->info("NO CHANGES MADE");
        $this->info("================================================================================");

        return 0;
    }

    /**
     * Render the audit report for a single tenant matching the required format
     */
    protected function renderTenantAudit(array $audit): void
    {
        $this->line("--------------------------------------------------------------------------------");
        $this->line("Tenant:         " . $audit['tenant'] . " [Subscription #{$audit['subscription_id']}]");
        $this->line("Database:       " . $audit['database']);
        $this->line("Site directory: " . $audit['site_directory'] . ($audit['site_directory_exists'] ? " (Exists)" : " (Missing)"));
        $this->line("");

        if ($audit['version_info']) {
            $ver = $audit['version_info'];
            $this->line("OpenEMR version:   {$ver['v_major']}.{$ver['v_minor']}.{$ver['v_patch']}");
            $this->line("Database revision: {$ver['v_database']}");
            $this->line("ACL revision:      {$ver['v_acl']}");
        } else {
            $this->line("OpenEMR version:   UNKNOWN");
            $this->line("Database revision: UNKNOWN");
            $this->line("ACL revision:      UNKNOWN");
        }

        if ($audit['version_compatible']) {
            $this->info("Compatibility:     COMPATIBLE (OpenEMR 8.3.0 Canonical Schema)");
        } else {
            $this->error("Compatibility:     UNSUPPORTED (" . implode(', ', $audit['compatibility_errors']) . ")");
        }
        $this->line("");

        $this->line("Canonical globals discovered: " . $audit['canonical_globals_discovered']);
        $this->line("Existing globals:             " . $audit['existing_globals_count']);
        $this->line("Missing globals:              " . $audit['missing_globals_count']);
        $this->line("Existing canonical globals:   " . $audit['existing_canonical_globals_count']);
        $this->line("Existing custom globals:      " . $audit['existing_custom_globals_count']);
        $this->line("");

        $byCat = $audit['missing_globals_by_category'];
        $this->line("Missing safe defaults:               " . count($byCat['safe_static_default']));
        $this->line("Dynamic values requiring generation: " . count($byCat['generated_dynamic']));
        $this->line("Environment-specific values:         " . count($byCat['environment_path']));
        $this->line("Security-sensitive values:           " . count($byCat['security_secret']));
        $this->line("Tenant-specific values:              " . count($byCat['tenant_specific']));
        $this->line("");

        $acl = $audit['acl_audit'];
        $this->line("ACL:");
        $this->line("  Canonical ACO Sections: " . ($acl['canonical_counts']['aco_sections'] ?? 0) . " | Existing: " . ($acl['existing_counts']['sections'] ?? 0));
        $this->line("  Canonical ACOs:         " . ($acl['canonical_counts']['acos'] ?? 0) . " | Existing: " . ($acl['existing_counts']['acos'] ?? 0));
        $this->line("  Canonical ARO Groups:   " . ($acl['canonical_counts']['groups'] ?? 0) . " | Existing: " . ($acl['existing_counts']['groups'] ?? 0));
        $this->line("  Canonical ACL Rules:    " . ($acl['canonical_acl_rules_count'] ?? 19) . " | Existing: " . ($acl['existing_acl_rules_count'] ?? 0) . " | Missing: " . ($acl['missing_acl_rules_count'] ?? 0));
        $this->line("  Canonical ACO Mappings: " . ($acl['canonical_aco_mappings_count'] ?? 203) . " | Existing: " . ($acl['existing_aco_mappings_count'] ?? 0) . " | Missing: " . ($acl['missing_aco_mappings_count'] ?? 0));
        $this->line("  Existing Group Maps:    " . ($acl['existing_counts']['group_aro_maps'] ?? 0));
        
        $this->line("  Missing sections: " . (empty($acl['missing_sections']) ? "None (0)" : count($acl['missing_sections']) . " missing (" . implode(', ', array_slice($acl['missing_sections'], 0, 5)) . (count($acl['missing_sections']) > 5 ? '...' : '') . ")"));
        $this->line("  Missing ACOs:     " . (empty($acl['missing_acos']) ? "None (0)" : count($acl['missing_acos']) . " missing"));
        $this->line("  Missing groups:   " . (empty($acl['missing_groups']) ? "None (0)" : count($acl['missing_groups']) . " missing (" . implode(', ', $acl['missing_groups']) . ")"));
        
        // Per-role rule breakdown
        $roles = ['admin' => 'Administrators', 'doc' => 'Physicians', 'clin' => 'Clinicians', 'front' => 'Front Office', 'back' => 'Accounting', 'breakglass' => 'Emergency Login'];
        foreach ($roles as $rKey => $rLabel) {
            $mRules = $acl['missing_rules_by_role'][$rKey] ?? [];
            $this->line("  {$rLabel} ({$rKey}) missing rules: " . (empty($mRules) ? "None (0) [PASS]" : count($mRules) . " missing [FAIL] (" . implode('; ', $mRules) . ")"));
        }
        $this->line("  Missing mappings summary: " . (empty($acl['missing_mappings']) ? "None (0) [PASS]" : implode('; ', $acl['missing_mappings']) . " [FAIL]"));
        if (isset($acl['carecoordination_module_acl']) && is_array($acl['carecoordination_module_acl'])) {
            $modAcl = $acl['carecoordination_module_acl'];
            if (isset($modAcl['allowed'])) {
                $statusStr = $modAcl['allowed'] ? "Allowed (module: {$modAcl['mod_id']}, group: {$modAcl['group_id']}, section: {$modAcl['section_id']}) [PASS]" : "MISSING [FAIL]";
                $this->line("  Carecoordination Module ACL: " . $statusStr);
            }
        }
        $this->line("");

        $admin = $audit['admin_user_audit'];
        $adminUserStatus = $admin['users_record'] ? "Present (ID: {$admin['users_record']['id']}, {$admin['users_record']['fname']} {$admin['users_record']['lname']})" : "MISSING";
        $adminAclStatus = $admin['gacl_mapped'] ? "Mapped (admin group)" : "UNMAPPED";
        $this->line("Admin user:        " . $adminUserStatus);
        $this->line("Admin ACL mapping: " . $adminAclStatus);
        $this->line("");

        $health = $audit['health_invariants'];
        $tabsStatus = $health['theme_tabs_layout']['exists'] ? "{$health['theme_tabs_layout']['value']} [{$health['theme_tabs_layout']['status']}]" : "MISSING [FAIL]";
        $patientFormStatus = $health['full_new_patient_form']['exists'] ? "{$health['full_new_patient_form']['value']} [{$health['full_new_patient_form']['status']}]" : "MISSING [FAIL]";
        $this->line("Health:");
        $this->line("  theme_tabs_layout:     " . $tabsStatus);
        $this->line("  full_new_patient_form: " . $patientFormStatus);
        $this->line("");

        $this->line("Recommended future repair actions:");
        if (empty($audit['recommended_actions'])) {
            $this->info("  No repair actions needed. Tenant database is fully synchronized.");
        } else {
            foreach ($audit['recommended_actions'] as $action) {
                $this->line("  -> " . $action);
            }
        }
        $this->line("");
    }
}

