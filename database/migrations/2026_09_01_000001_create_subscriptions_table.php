<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->string('doctor_name');
            $table->string('email');
            $table->string('practice_type')->default('General Practice');
            $table->string('region')->default('LC');
            $table->string('stripe_customer_id')->nullable();
            $table->string('stripe_payment_intent_id')->nullable();
            $table->string('stripe_subscription_id')->nullable();
            $table->decimal('amount', 8, 2)->default(80.00);
            $table->string('currency', 10)->default('usd');
            $table->string('payment_status')->default('pending'); // pending, succeeded, failed
            $table->string('setup_cost_status')->default('billed_separately'); // setup cost note tracking
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
