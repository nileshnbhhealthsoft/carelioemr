<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Subscription;
use Illuminate\Support\Facades\Hash;

// Landing Page View
Route::get('/', function () {
    return view('landing');
});

// Admin Login Form View
Route::get('/admin/login', function () {
    if (session()->has('admin_authenticated')) {
        return redirect('/admin/dashboard');
    }
    return view('admin.login');
});

// Portal Login Action (MySQL User Authentication)
Route::post('/admin/login', function (Request $request) {
    $request->validate([
        'email' => 'required|string',
        'password' => 'required|string',
    ]);

    $loginInput = trim($request->email);
    $user = User::where('email', $loginInput)->orWhere('name', $loginInput)->first();

    if ($user && Hash::check($request->password, $user->password) && $user->is_admin) {
        session([
            'admin_authenticated' => true,
            'admin_user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ]
        ]);
        return redirect('/admin/dashboard');
    }

    return redirect('/admin/login')->with('error', 'Invalid username or password.');
});

// Admin Dashboard View (Session Guarded)
Route::get('/admin/dashboard', function () {
    if (!session()->has('admin_authenticated')) {
        return redirect('/admin/login');
    }
    $subscribers = Subscription::orderBy('created_at', 'desc')->get();
    return view('admin.dashboard', compact('subscribers'));
});

// Admin Logout Action
Route::get('/admin/logout', function () {
    session()->forget(['admin_authenticated', 'admin_user']);
    return redirect('/');
});

// Admin Tenant Review Actions
Route::post('/admin/subscribers/{id}/approve', [\App\Http\Controllers\AdminSubscriptionReviewController::class, 'approve']);
Route::post('/admin/subscribers/{id}/reject', [\App\Http\Controllers\AdminSubscriptionReviewController::class, 'reject']);

// OpenEMR Tenant Portal Route - uses /tenant/ prefix to avoid conflict with public/openemr symlink
Route::get('/tenant/{tenant_slug}', function ($tenant_slug) {
    $subscription = Subscription::where('tenant_slug', $tenant_slug)->first();
    return view('openemr.tenant_portal', compact('tenant_slug', 'subscription'));
});
