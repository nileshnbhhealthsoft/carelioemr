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
        'paid_at'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
    ];
}
