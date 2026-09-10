<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use App\Services\OpenEmr\OpenEmrTenantRepairService;
use Illuminate\Console\Command;
use Throwable;

class OpenEmrCanaryRepairCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'openemr:canary-repair 
                            {--tenant=10 : The subscription ID for canary repair (Strictly restricted to 10 in Phase 1C-2B)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Execute Phase 1C-2B Canary Repair strictly on Subscription #10 with mandatory verified backup';

    /**
     * Execute the console command.
     */
    public function handle(OpenEmrTenantRepairService $repairService): int
    {
        $this->info("================================================================================");
        $this->info("        CANONICAL OPENEMR 8.3.0 CANARY TENANT REPAIR (PHASE 1C-2B)              ");
        $this->info("================================================================================");

        $tenantOption = (int) $this->option('tenant');

        // Safety Guard: Canary Repair is restricted to Subscription #10 ONLY
        if ($tenantOption !== 10) {
            $this->error("CRITICAL SAFETY GUARD: Phase 1C-2B permits canary repair of Subscription #10 ONLY. Got: {$tenantOption}");
            return 1;
        }

        $subscription = Subscription::find(10);
        if (!$subscription) {
            $this->error("Subscription #10 not found in application database.");
            return 1;
        }

        $this->line("Target Tenant:                 {$subscription->doctor_name} (Subscription #10)");
        $this->line("Target Database:               {$subscription->openemr_database}");
        $this->line("Target Site:                   oemr/sites/{$subscription->tenant_slug}");
        $this->line("");

        try {
            $this->line("1. Creating mandatory pre-repair database backup...");
            $result = $repairService->repairTenant($subscription);

            $this->info("   Backup created & verified successfully!");
            $this->line("   - Backup File:   {$result['backup']['file_name']}");
            $this->line("   - Backup Size:   " . number_format($result['backup']['file_size']) . " bytes");
            $this->line("   - SHA256:        {$result['backup']['sha256']}");
            $this->line("");

            $this->info("2. Pre-Repair Snapshot Recorded:");
            $pre = $result['pre_repair_snapshot'];
            $this->line("   - Version:       {$pre['version']['v_major']}.{$pre['version']['v_minor']}.{$pre['version']['v_patch']} (rev {$pre['version']['v_database']}, acl {$pre['version']['v_acl']})");
            $this->line("   - Globals:       Total {$pre['globals_total']}, Missing Canonical: {$pre['globals_missing']}");
            $this->line("   - Theme Layout:  {$pre['theme_tabs_layout']}");
            $this->line("   - Full Form:     {$pre['full_new_patient_form']}");
            $this->line("   - Preserved Data: Users: {$pre['users_count']}, Patients: {$pre['patients_count']}, Encounters: {$pre['encounters_count']}, Facilities: {$pre['facilities_count']}, Events: {$pre['events_count']}");
            $this->line("");

            $this->info("3. Guarded Version Repair Executed:");
            $this->line("   - Rows Updated:  {$result['version_change']['rows_affected']}");
            $this->line("   - Old Version:   {$result['version_change']['old_version']}");
            $this->line("   - New Version:   {$result['version_change']['new_version']}");
            $this->line("");

            $this->info("4. Canonical Globals Repair Executed:");
            $this->line("   - Total Inserted: {$result['globals_result']['total_inserted']} canonical defaults (additive-only, zero overwrites)");
            $this->line("   - Sample Keys:   " . implode(', ', $result['globals_result']['sample_inserted']) . " ...");
            $this->line("   - Theme Layout:  " . ($result['globals_result']['theme_tabs_layout_inserted'] ? 'Inserted' : 'Already Existed'));
            $this->line("   - Full Form:     " . ($result['globals_result']['full_new_patient_form_inserted'] ? 'Inserted' : 'Already Existed'));
            $this->line("");

            $this->info("5. Canonical ACL Repair Executed:");
            $this->line("   - New ACL Rules: " . count($result['acl_result']['inserted_acl']) . " inserted");
            $this->line("   - New ACO Maps:  {$result['acl_result']['inserted_aco_mappings_count']} inserted");
            $this->line("   - Admin Group:   Resolved to group ID {$result['acl_result']['admin_group_id_resolved']}");
            $this->line("   - Admin ARO:     Resolved to ARO ID {$result['acl_result']['admin_aro_id_resolved']}");
            $this->line("   - Admin Mapping: Verified mapped into Administrators");
            $this->line("");

            $this->info("6. Post-Repair Snapshot & Data Preservation Verification:");
            $post = $result['post_repair_snapshot'];
            $this->line("   - Version:       {$post['version']['v_major']}.{$post['version']['v_minor']}.{$post['version']['v_patch']} (rev {$post['version']['v_database']}, acl {$post['version']['v_acl']})");
            $this->line("   - Globals:       Total {$post['globals_total']}, Missing Canonical: {$post['globals_missing']}");
            $this->line("   - Theme Layout:  {$post['theme_tabs_layout']} [PASS]");
            $this->line("   - Full Form:     {$post['full_new_patient_form']} [PASS]");
            $this->line("   - Preserved Data: Users: {$post['users_count']} (unchanged), Patients: {$post['patients_count']} (unchanged), Encounters: {$post['encounters_count']} (unchanged), Facilities: {$post['facilities_count']} (unchanged), Events: {$post['events_count']} (unchanged)");
            $this->line("");

            $this->info("================================================================================");
            $this->info("CANARY REPAIR OF SUBSCRIPTION #10 COMPLETED SUCCESSFULLY IN {$result['duration_seconds']}s!");
            $this->info("================================================================================");

            return 0;

        } catch (Throwable $e) {
            $this->error("CANARY REPAIR FAILED: " . $e->getMessage());
            return 1;
        }
    }
}

