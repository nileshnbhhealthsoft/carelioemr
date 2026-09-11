<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Subscription;
use Carbon\Carbon;
use App\Mail\CustomerSiteReadyMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class AdminSubscriptionReviewController extends Controller
{
    /**
     * Platform Admin Approve Tenant Action
     */
    public function approve(Request $request, $id)
    {
        // 1. Strict Backend Authorization: Platform Admin Session ONLY
        $adminUser = $this->resolveAuthenticatedAdmin($request);
        if (!$adminUser) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Unauthorized. Platform Admin session required.'], 403);
            }
            return redirect('/admin/login')->with('error', 'Unauthorized access.');
        }

        $subscription = Subscription::findOrFail($id);

        // 2. Validate prerequisites: provision_status = completed AND review_status = pending_review
        if ($subscription->provision_status !== 'completed') {
            $msg = 'Cannot approve: Tenant provisioning is not completed (current status: ' . ($subscription->provision_status ?? 'pending') . ').';
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'error' => $msg], 422);
            }
            return redirect('/admin/dashboard')->with('error', $msg);
        }

        if ($subscription->review_status !== 'pending_review') {
            $msg = 'Cannot approve: Subscription is not in pending_review status (current status: ' . ($subscription->review_status ?? 'none') . ').';
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'error' => $msg], 422);
            }
            return redirect('/admin/dashboard')->with('error', $msg);
        }

        // 3. Set review_status = approved, reviewed_at = now, reviewed_by = authenticated admin
        $subscription->update([
            'review_status' => 'approved',
            'reviewed_at' => Carbon::now(),
            'reviewed_by' => $adminUser,
            'rejection_reason' => null,
        ]);

        // Phase 3E: Send final customer Site Ready email after successful admin approval
        try {
            if (!empty($subscription->email)) {
                Mail::to($subscription->email)->send(new CustomerSiteReadyMail($subscription));
                Log::info("Phase 3E: Customer Site Ready email dispatched to {$subscription->email} for subscription #{$subscription->id}.");
            }
        } catch (\Exception $mailEx) {
            Log::error("Phase 3E: Failed to send customer Site Ready email for subscription #{$subscription->id}: " . $mailEx->getMessage());
        }

        $msg = "Tenant for {$subscription->doctor_name} has been approved successfully.";
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $msg,
                'subscription' => $subscription->fresh()
            ]);
        }
        return redirect('/admin/dashboard')->with('success', $msg);
    }

    /**
     * Platform Admin Reject Tenant Action
     */
    public function reject(Request $request, $id)
    {
        // 1. Strict Backend Authorization: Platform Admin Session ONLY
        $adminUser = $this->resolveAuthenticatedAdmin($request);
        if (!$adminUser) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Unauthorized. Platform Admin session required.'], 403);
            }
            return redirect('/admin/login')->with('error', 'Unauthorized access.');
        }

        $subscription = Subscription::findOrFail($id);

        // 2. Validate prerequisites: provision_status = completed AND review_status = pending_review
        if ($subscription->provision_status !== 'completed') {
            $msg = 'Cannot reject: Tenant provisioning is not completed (current status: ' . ($subscription->provision_status ?? 'pending') . ').';
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'error' => $msg], 422);
            }
            return redirect('/admin/dashboard')->with('error', $msg);
        }

        if ($subscription->review_status !== 'pending_review') {
            $msg = 'Cannot reject: Subscription is not in pending_review status (current status: ' . ($subscription->review_status ?? 'none') . ').';
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'error' => $msg], 422);
            }
            return redirect('/admin/dashboard')->with('error', $msg);
        }

        $request->validate(['rejection_reason' => 'nullable|string|max:1000']);

        // 3. Set review_status = rejected, reviewed_at = now, reviewed_by = authenticated admin
        $subscription->update([
            'review_status' => 'rejected',
            'reviewed_at' => Carbon::now(),
            'reviewed_by' => $adminUser,
            'rejection_reason' => $request->input('rejection_reason'),
        ]);

        // Note: Do NOT delete tenant/database on rejection. Do NOT modify OEMR/OpenEMR.

        $msg = "Tenant for {$subscription->doctor_name} has been rejected.";
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $msg,
                'subscription' => $subscription->fresh()
            ]);
        }
        return redirect('/admin/dashboard')->with('success', $msg);
    }

    /**
     * Resolve authenticated platform admin ONLY from active session.
     * Rejects all arbitrary Bearer tokens, X-Admin-Token, and X-Admin-User.
     */
    protected function resolveAuthenticatedAdmin(Request $request): ?string
    {
        if (session()->has('admin_authenticated') && session('admin_authenticated') === true) {
            return session('admin_user.email') ?? session('admin_user.name') ?? 'admin@carelioemr.com';
        }

        return null;
    }
}
