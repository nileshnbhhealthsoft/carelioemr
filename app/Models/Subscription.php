<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'doctor_name',
        'email',
        'practice_type',
        'region',
        'tenant_slug',
        'openemr_database',
        'openemr_site_url',
        'provision_status',
        'provision_error',
        'stripe_customer_id',
        'stripe_payment_intent_id',
        'stripe_subscription_id',
        'amount',
        'currency',
        'payment_status',
        'setup_cost_status',
        'paid_at',
        'review_status',
        'reviewed_at',
        'reviewed_by',
        'rejection_reason',
        'initial_password_encrypted',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'initial_password_encrypted' => 'encrypted',
    ];

    /**
     * Get canonical OpenEMR tenant login URL dynamically from app and oemr configuration
     */
    public function getCanonicalSiteUrl(): string
    {
        $baseUrl = rtrim((string) config('app.url'), '/');
        $webPath = config('oemr.web_path') ? ('/' . trim((string) config('oemr.web_path'), '/')) : '';
        $tenantSlug = $this->tenant_slug ?? '';

        return $baseUrl . $webPath . '/interface/login/login.php?site=' . rawurlencode($tenantSlug);
    }
}
