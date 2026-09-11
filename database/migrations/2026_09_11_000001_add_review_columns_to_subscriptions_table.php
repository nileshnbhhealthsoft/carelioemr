<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            if (!Schema::hasColumn('subscriptions', 'review_status')) {
                $table->string('review_status', 30)->nullable()->after('provision_error');
            }
            if (!Schema::hasColumn('subscriptions', 'reviewed_at')) {
                $table->timestamp('reviewed_at')->nullable()->after('review_status');
            }
            if (!Schema::hasColumn('subscriptions', 'reviewed_by')) {
                $table->string('reviewed_by')->nullable()->after('reviewed_at');
            }
            if (!Schema::hasColumn('subscriptions', 'rejection_reason')) {
                $table->text('rejection_reason')->nullable()->after('reviewed_by');
            }
        });

        // Initialize existing completed subscriptions to pending_review
        DB::table('subscriptions')
            ->where('provision_status', 'completed')
            ->whereNull('review_status')
            ->update(['review_status' => 'pending_review']);
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn(['review_status', 'reviewed_at', 'reviewed_by', 'rejection_reason']);
        });
    }
};
