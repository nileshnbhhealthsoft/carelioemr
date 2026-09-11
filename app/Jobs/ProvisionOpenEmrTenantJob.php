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
        $provisionSuccess = $provisioningService->provisionTenant($this->subscription);
        $this->subscription->refresh();

        if ($provisionSuccess && $this->subscription->provision_status === 'completed') {
            $this->subscription->update([
                'review_status' => 'pending_review',
            ]);

            $adminEmail = config('mail.admin_notification_email') ?: env('ADMIN_NOTIFICATION_EMAIL');
            if (!empty($adminEmail)) {
                try {
                    \Illuminate\Support\Facades\Mail::to($adminEmail)->send(new \App\Mail\AdminTenantReadyForReviewMail($this->subscription));
                } catch (\Exception $adminMailEx) {
                    Log::warning('Admin tenant ready notification error from job: ' . $adminMailEx->getMessage());
                }
            }
        }
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
