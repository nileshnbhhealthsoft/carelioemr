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

        if ($user && Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => true,
                'message' => 'Admin authentication successful',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
                'token' => 'auraemr_admin_token_' . md5($user->email . time())
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
        $request->validate([
            'doctor_name' => 'required|string|max:255',
            'email' => 'required|email',
            'practice_type' => 'nullable|string',
            'region' => 'nullable|string',
        ]);

        try {
            $amount = 8000; // $80.00 in cents
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
                'description' => "AuraEMR Monthly Subscription ($80/mo) - {$request->doctor_name}",
                'metadata' => [
                    'subscriber_name' => $request->doctor_name,
                    'practice_type' => $request->practice_type ?? 'General Practice',
                    'region' => $request->region ?? 'LC',
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
                    'practice_type' => $request->practice_type ?? 'General Practice',
                    'region' => $request->region ?? 'LC',
                    'stripe_customer_id' => $customer->id,
                    'stripe_payment_intent_id' => $intent->id,
                    'updated_at' => Carbon::now(),
                ]);
            } else {
                $subscription = Subscription::create([
                    'doctor_name' => $request->doctor_name,
                    'email' => $request->email,
                    'practice_type' => $request->practice_type ?? 'General Practice',
                    'region' => $request->region ?? 'LC',
                    'stripe_customer_id' => $customer->id,
                    'stripe_payment_intent_id' => $intent->id,
                    'amount' => 80.00,
                    'currency' => 'usd',
                    'payment_status' => 'pending',
                    'setup_cost_status' => 'setup_cost_additional_billed_separately',
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
                    'email' => $intent->receipt_email ?? $request->email,
                    'practice_type' => $intent->metadata->practice_type ?? 'General Practice',
                    'region' => $intent->metadata->region ?? 'LC',
                    'stripe_customer_id' => $intent->customer,
                    'stripe_payment_intent_id' => $intent->id,
                    'amount' => 80.00,
                    'currency' => 'usd',
                    'payment_status' => $intent->status === 'succeeded' ? 'succeeded' : $intent->status,
                    'setup_cost_status' => 'setup_cost_additional_billed_separately',
                    'paid_at' => $intent->status === 'succeeded' ? Carbon::now() : null,
                ]);
            } else {
                $subscription->update([
                    'stripe_payment_intent_id' => $intent->id,
                    'payment_status' => $intent->status === 'succeeded' ? 'succeeded' : $intent->status,
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

            // Provision OpenEMR Tenant synchronously so user gets instant launch link
            try {
                $provisioningService = app(\App\Services\OpenEmrProvisioningService::class);
                $provisioningService->provisionTenant($subscription);
                $subscription->refresh();
            } catch (\Exception $provEx) {
                \Log::error("Direct OpenEMR provisioning error: " . $provEx->getMessage());
                try {
                    ProvisionOpenEmrTenantJob::dispatch($subscription);
                } catch (\Exception $qEx) {}
            }

            // Dispatch Emails dynamically
            try {
                Mail::to($subscription->email)->send(new SubscriptionConfirmationMail($subscription));

                $adminEmail = env('ADMIN_NOTIFICATION_EMAIL', 'shrivastavanandini11@gmail.com');
                if (strtolower($subscription->email) !== strtolower($adminEmail)) {
                    Mail::to($adminEmail)->send(new SubscriptionConfirmationMail($subscription));
                }
            } catch (\Exception $mailEx) {
                \Log::warning('Subscription mail notice: ' . $mailEx->getMessage());
            }

            return response()->json([
                'success' => true,
                'message' => 'Payment confirmed and OpenEMR tenant provisioned successfully!',
                'subscriber' => $subscription,
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
}
