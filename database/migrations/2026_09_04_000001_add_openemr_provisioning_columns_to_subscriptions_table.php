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
        Schema::table('subscriptions', function (Blueprint $table) {
            if (!Schema::hasColumn('subscriptions', 'tenant_slug')) {
                $table->string('tenant_slug')->nullable()->after('region');
            }
            if (!Schema::hasColumn('subscriptions', 'openemr_database')) {
                $table->string('openemr_database')->nullable()->after('tenant_slug');
            }
            if (!Schema::hasColumn('subscriptions', 'openemr_site_url')) {
                $table->string('openemr_site_url')->nullable()->after('openemr_database');
            }
            if (!Schema::hasColumn('subscriptions', 'provision_status')) {
                $table->string('provision_status')->default('pending')->after('openemr_site_url'); // pending, provisioning, completed, failed
            }
            if (!Schema::hasColumn('subscriptions', 'provision_error')) {
                $table->text('provision_error')->nullable()->after('provision_status');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn(['tenant_slug', 'openemr_database', 'openemr_site_url', 'provision_status', 'provision_error']);
        });
    }
};
