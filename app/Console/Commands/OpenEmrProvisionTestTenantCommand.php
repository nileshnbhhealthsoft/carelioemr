<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use App\Services\OpenEmrProvisioningService;
use Illuminate\Console\Command;
use Throwable;

class OpenEmrProvisionTestTenantCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'openemr:provision-test-tenant {--name= : Custom doctor name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Provision ONE completely new disposable test tenant through canonical OpenEMR 8.3.0 provisioning flow';

    /**
     * Execute the console command.
     */
    public function handle(OpenEmrProvisioningService $provisioningService): int
    {
        $this->info("================================================================================");
        $this->info("   CANONICAL OPENEMR 8.3.0 DISPOSABLE TEST TENANT PROVISIONING (PHASE 1B)   ");
        $this->info("================================================================================");

        $name = $this->option('name') ?: 'Dr. Disposable Test Phase1B';

        $this->line("1. Creating fresh test Subscription in Laravel database...");
        $subscription = Subscription::create([
            'doctor_name' => $name,
            'email' => 'disposable_phase1b_' . time() . '@example.com',
            'practice_type' => 'Phase1B Canonical Test Clinic',
            'region' => 'Global Test Region',
            'amount' => 99.00,
            'currency' => 'usd',
            'payment_status' => 'succeeded',
            'provision_status' => 'pending',
        ]);

        $this->info("   -> Subscription #{$subscription->id} created for [{$subscription->doctor_name}]");
        $this->line("2. Launching canonical tenant provisioning workflow...");

        $startTime = microtime(true);

        try {
            $provisioningService->provisionTenant($subscription);
            $elapsed = round(microtime(true) - $startTime, 2);

            $subscription->refresh();
            $this->info("================================================================================");
            $this->info("PROVISIONING SUCCESSFUL in {$elapsed} seconds!");
            $this->line("Tenant Slug: " . $subscription->tenant_slug);
            $this->line("Database:    " . $subscription->openemr_database);
            $this->line("Site URL:    " . $subscription->openemr_site_url);
            $this->info("================================================================================");
            return 0;

        } catch (Throwable $e) {
            $subscription->refresh();
            $this->error("PROVISIONING FAILED: " . $e->getMessage());
            $this->line($e->getTraceAsString());
            return 1;
        }
    }
}

