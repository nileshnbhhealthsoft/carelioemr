<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\StripeSubscriptionController;
use App\Http\Middleware\EnsureAdminAuthenticated;

// Public Routes (Checkout & Payment Processing)
Route::post('/admin/login', [StripeSubscriptionController::class, 'adminLogin']);
Route::post('/stripe/create-intent', [StripeSubscriptionController::class, 'createPaymentIntent']);
Route::post('/stripe/confirm', [StripeSubscriptionController::class, 'confirmPayment']);

// Protected Admin Routes (Requires Bearer Token Authorization Header)
Route::middleware([EnsureAdminAuthenticated::class])->group(function () {
    Route::get('/subscribers', [StripeSubscriptionController::class, 'getSubscribers']);
});
