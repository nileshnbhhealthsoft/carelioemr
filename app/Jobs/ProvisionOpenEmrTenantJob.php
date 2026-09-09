<?php

namespace App\Jobs;

use App\Models\Subscription;
use App\Services\OpenEmrProvisioningService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProvisionOpenEmrTenantJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $subscription;
    public $tries = 3;
    public $backoff = [30, 60, 120]; // Retry backoff in seconds

    /**
     * Create a new job instance.
     */
    public function __construct(Subscription $subscription)
    {
        $this->subscription = $subscription;
    }

    /**
     * Execute the job.
     */
    public function handle(OpenEmrProvisioningService $provisioningService): void
    {
        Log::info("Executing ProvisionOpenEmrTenantJob for Subscription #{$this->subscription->id}");
        $provisioningService->provisionTenant($this->subscription);
    }

    /**
     * Handle job failure.
     */
    public function failed(Throwable $exception): void
    {
        Log::error("ProvisionOpenEmrTenantJob Failed for Subscription #{$this->subscription->id}: {$exception->getMessage()}");
        $this->subscription->update([
            'provision_status' => 'failed',
            'provision_error' => $exception->getMessage(),
        ]);
    }
}
