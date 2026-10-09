<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            if (!Schema::hasColumn('subscriptions', 'billing_cycle')) {
                $table->string('billing_cycle', 20)->default('monthly')->after('setup_cost_status');
            }

            if (!Schema::hasColumn('subscriptions', 'billing_interval_months')) {
                $table->unsignedSmallInteger('billing_interval_months')->default(1)->after('billing_cycle');
            }

            if (!Schema::hasColumn('subscriptions', 'discount_amount')) {
                $table->decimal('discount_amount', 8, 2)->default(0)->after('billing_interval_months');
            }
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            foreach (['discount_amount', 'billing_interval_months', 'billing_cycle'] as $column) {
                if (Schema::hasColumn('subscriptions', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
