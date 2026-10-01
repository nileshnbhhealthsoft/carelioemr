<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Subscription;
use App\Models\User;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use Stripe\Customer;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Mail\SubscriptionConfirmationMail;
use App\Mail\RegistrationAcknowledgementMail;
use App\Mail\AdminTenantReadyForReviewMail;
use App\Jobs\ProvisionOpenEmrTenantJob;

class StripeSubscriptionController extends Controller
{
    public function __construct()
    {
        $stripeSecret = config('services.stripe.secret') ?? env('STRIPE_SECRET_KEY');
        Stripe::setApiKey($stripeSecret);
    }

    /**
     * Dynamic Admin Login querying auraemr database users table
     */
    public function adminLogin(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if ($user && Hash::check($request->password, $user->password) && $user->is_admin) {
            return response()->json([
                'success' => true,
                'message' => 'Admin authentication successful',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
                'token' => 'carelioemr_admin_token_' . md5($user->email . time())
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Invalid credentials'
        ], 401);
    }

    /**
     * Create Stripe Payment Intent & record initial subscriber dynamically
     */
    public function createPaymentIntent(Request $request)
    {
        $normalizedEmail = strtolower(trim((string) $request->email));
        $request->merge(['email' => $normalizedEmail]);

        $request->validate([
            'doctor_name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                function ($attribute, $value, $fail) {
                    $normalized = strtolower(trim((string) $value));
                    $hasCustomerEmail = \Illuminate\Support\Facades\Schema::hasColumn('subscriptions', 'customer_email');
                    $hasStripeStatus = \Illuminate\Support\Facades\Schema::hasColumn('subscriptions', 'stripe_status');

                    $exists = Subscription::where(function ($q) use ($normalized, $hasCustomerEmail) {
                            $q->where('email', $normalized);
                            if ($hasCustomerEmail) {
                                $q->orWhere('customer_email', $normalized);
                            }
                        })
                        ->where(function ($query) use ($hasStripeStatus) {
                            if ($hasStripeStatus) {
                                $query->whereIn('stripe_status', ['active', 'trialing', 'incomplete']);
                            }
                            $query->orWhereIn('provision_status', ['completed', 'provisioning'])
                                  ->orWhereNotNull('openemr_database')
                                  ->orWhereIn('payment_status', ['succeeded', 'paid', 'active', 'trialing'])
                                  ->orWhere(function ($q2) {
                                      $q2->where('provision_status', 'pending')
                                         ->where(function ($q3) {
                                             $q3->whereNotNull('paid_at')
                                                ->orWhereIn('payment_status', ['succeeded', 'paid']);
                                         });
                                  });
                        })
                        ->exists();

                    if ($exists) {
                        $fail('An active account or subscription is already associated with this email address. Please log in to your tenant portal or contact support.');
                    }
                },
            ],
            'site_name' => 'nullable|string|max:255',
            'practice_type' => 'nullable|string',
            'region' => 'nullable|string',
            'billing_cycle' => 'nullable|in:monthly,yearly',
        ]);

        try {
            $billingCycle = $request->billing_cycle === 'yearly' ? 'yearly' : 'monthly';
            $plan = $this->getSubscriptionPlan($billingCycle);
            $siteName = trim((string) $request->site_name) ?: (($request->doctor_name ?? 'Doctor') . ' Practice');

            $amount = $plan['amount_cents'];
            $currency = 'usd';

            // 1. Create or Find Stripe Customer dynamically
            $customers = Customer::all(['email' => $request->email, 'limit' => 1]);
            if (count($customers->data) > 0) {
                $customer = $customers->data[0];
            } else {
                $customer = Customer::create([
                    'email' => $request->email,
                    'name' => $request->doctor_name,
                    'metadata' => [
                        'site_name' => $siteName,
                        'practice_type' => $request->practice_type ?? 'General Practice',
                        'region' => $request->region ?? 'LC',
                    ]
                ]);
            }

            // 2. Create Stripe Payment Intent dynamically
            $intent = PaymentIntent::create([
                'amount' => $amount,
                'currency' => $currency,
                'customer' => $customer->id,
                'receipt_email' => $request->email,
                'description' => $plan['description'] . " - {$request->doctor_name}",
                'metadata' => [
                    'subscriber_name' => $request->doctor_name,
                    'site_name' => $siteName,
                    'practice_type' => $request->practice_type ?? 'General Practice',
                    'region' => $request->region ?? 'LC',
                    'billing_cycle' => $billingCycle,
                    'billing_interval_months' => (string) $plan['interval_months'],
                    'discount_amount' => number_format($plan['discount_amount'], 2, '.', ''),
                    'setup_cost_note' => 'Setup cost will be an additional cost'
                ],
                'automatic_payment_methods' => [
                    'enabled' => true,
                ],
            ]);

            // 3. Update existing pending attempt or create new subscriber record dynamically
            $subscription = Subscription::where('email', $request->email)
                ->where('payment_status', 'pending')
                ->first();

            if ($subscription) {
                $subscription->update([
                    'doctor_name' => $request->doctor_name,
                    'site_name' => $siteName,
                    'practice_type' => $request->practice_type ?? 'General Practice',
                    'region' => $request->region ?? 'LC',
                    'stripe_customer_id' => $customer->id,
                    'stripe_payment_intent_id' => $intent->id,
                    'amount' => $plan['amount_decimal'],
                    'billing_cycle' => $billingCycle,
                    'billing_interval_months' => $plan['interval_months'],
                    'discount_amount' => $plan['discount_amount'],
                    'updated_at' => Carbon::now(),
                ]);
            } else {
                $subscription = Subscription::create([
                    'doctor_name' => $request->doctor_name,
                    'site_name' => $siteName,
                    'email' => $request->email,
                    'practice_type' => $request->practice_type ?? 'General Practice',
                    'region' => $request->region ?? 'LC',
                    'stripe_customer_id' => $customer->id,
                    'stripe_payment_intent_id' => $intent->id,
                    'amount' => $plan['amount_decimal'],
                    'currency' => 'usd',
                    'payment_status' => 'pending',
                    'provision_status' => 'pending',
                    'setup_cost_status' => 'setup_cost_additional_billed_separately',
                    'billing_cycle' => $billingCycle,
                    'billing_interval_months' => $plan['interval_months'],
                    'discount_amount' => $plan['discount_amount'],
                ]);
            }

            return response()->json([
                'clientSecret' => $intent->client_secret,
                'paymentIntentId' => $intent->id,
                'subscriptionId' => $subscription->id,
                'customerId' => $customer->id,
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Confirm Payment & Dispatch Asynchronous OpenEMR Tenant Provisioning Job!
     */
    public function confirmPayment(Request $request)
    {
        $request->validate([
            'payment_intent_id' => 'required|string',
        ]);

        try {
            $intent = PaymentIntent::retrieve($request->payment_intent_id);

            $subscription = Subscription::where('stripe_payment_intent_id', $request->payment_intent_id)->first();

            if (!$subscription && isset($intent->receipt_email)) {
                $subscription = Subscription::where('email', $intent->receipt_email)
                    ->where('payment_status', 'pending')
                    ->first();
            }

            if (!$subscription) {
                $subscription = Subscription::create([
                    'doctor_name' => $intent->metadata->subscriber_name ?? $request->doctor_name ?? 'Doctor',
                    'site_name' => $intent->metadata->site_name ?? $request->site_name ?? null,
                    'email' => $intent->receipt_email ?? $request->email,
                    'practice_type' => $intent->metadata->practice_type ?? 'General Practice',
                    'region' => $intent->metadata->region ?? 'LC',
                    'stripe_customer_id' => $intent->customer,
                    'stripe_payment_intent_id' => $intent->id,
                    'amount' => ($intent->amount_received ?? $intent->amount ?? 8000) / 100,
                    'currency' => 'usd',
                    'payment_status' => $intent->status === 'succeeded' ? 'succeeded' : $intent->status,
                    'provision_status' => 'pending',
                    'setup_cost_status' => 'setup_cost_additional_billed_separately',
                    'billing_cycle' => $intent->metadata->billing_cycle ?? 'monthly',
                    'billing_interval_months' => (int) ($intent->metadata->billing_interval_months ?? 1),
                    'discount_amount' => (float) ($intent->metadata->discount_amount ?? 0),
                    'paid_at' => $intent->status === 'succeeded' ? Carbon::now() : null,
                ]);
            } else {
                $subscription->update([
                    'stripe_payment_intent_id' => $intent->id,
                    'site_name' => $subscription->site_name ?: ($intent->metadata->site_name ?? $request->site_name ?? null),
                    'amount' => ($intent->amount_received ?? $intent->amount ?? ((float) $subscription->amount * 100)) / 100,
                    'payment_status' => $intent->status === 'succeeded' ? 'succeeded' : $intent->status,
                    'billing_cycle' => $intent->metadata->billing_cycle ?? $subscription->billing_cycle ?? 'monthly',
                    'billing_interval_months' => (int) ($intent->metadata->billing_interval_months ?? $subscription->billing_interval_months ?? 1),
                    'discount_amount' => (float) ($intent->metadata->discount_amount ?? $subscription->discount_amount ?? 0),
                    'paid_at' => $intent->status === 'succeeded' ? Carbon::now() : $subscription->paid_at,
                ]);
            }

            // Cleanup orphaned pending attempts
            if ($intent->status === 'succeeded' && $subscription->email) {
                Subscription::where('email', $subscription->email)
                    ->where('payment_status', 'pending')
                    ->where('id', '!=', $subscription->id)
                    ->delete();
            }

            // Send customer acknowledgement email immediately after payment confirmation and BEFORE provisioning
            try {
                if ($intent->status === 'succeeded' && $subscription->email) {
                    Mail::to($subscription->email)->send(new RegistrationAcknowledgementMail($subscription));
                }
            } catch (\Exception $mailEx) {
                \Log::warning('Registration acknowledgement mail error: ' . $mailEx->getMessage());
            }

            // Dispatch tenant provisioning job (sync in local dev, non-blocking queued database worker in production)
            if ($intent->status === 'succeeded') {
                ProvisionOpenEmrTenantJob::dispatch($subscription);
            }

            return response()->json([
                'success' => true,
                'message' => 'Payment confirmed and CarelioEMR tenant provisioning initiated successfully!',
                'subscriber' => $subscription->fresh(),
                'openemr_site_url' => $subscription->openemr_site_url,
                'tenant_slug' => $subscription->tenant_slug,
                'openemr_database' => $subscription->openemr_database,
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get List of All Subscribers from Database dynamically
     */
    public function getSubscribers()
    {
        $subscribers = Subscription::orderBy('created_at', 'desc')->get();
        return response()->json([
            'count' => $subscribers->count(),
            'subscribers' => $subscribers
        ]);
    }

    protected function getSubscriptionPlan(string $billingCycle): array
    {
        if ($billingCycle === 'yearly') {
            return [
                'amount_cents' => 88000,
                'amount_decimal' => 880.00,
                'discount_amount' => 80.00,
                'interval_months' => 12,
                'description' => 'CarelioEMR Yearly Subscription ($880/year, $80 discount)',
            ];
        }

        return [
            'amount_cents' => 8000,
            'amount_decimal' => 80.00,
            'discount_amount' => 0.00,
            'interval_months' => 1,
            'description' => 'CarelioEMR Monthly Subscription ($80/mo)',
        ];
    }
}
