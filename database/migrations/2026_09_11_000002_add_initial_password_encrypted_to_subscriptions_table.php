<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            if (!Schema::hasColumn('subscriptions', 'initial_password_encrypted')) {
                $table->text('initial_password_encrypted')->nullable()->after('rejection_reason');
            }
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            if (Schema::hasColumn('subscriptions', 'initial_password_encrypted')) {
                $table->dropColumn('initial_password_encrypted');
            }
        });
    }
};
