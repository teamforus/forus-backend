<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * @return void
     */
    public function up(): void
    {
        Schema::table('identity_provider_memberships', function (Blueprint $table) {
            $table->enum('account_type', ['employee', 'requester'])->default('employee')->after('employee_id');
            $table->enum('provisioning_status', ['active', 'disabled', 'deleted'])->nullable()->after('account_type');
            $table->string('scim_user_name')->nullable()->after('provisioning_status');

            $table->index(['connection_id', 'account_type', 'provisioning_status'], 'idp_memberships_provisioning_index');
            $table->index(['connection_id', 'scim_user_name'], 'idp_memberships_scim_user_name_index');
        });
    }

    /**
     * @return void
     */
    public function down(): void
    {
        Schema::table('identity_provider_memberships', function (Blueprint $table) {
            $table->dropIndex('idp_memberships_scim_user_name_index');
            $table->dropIndex('idp_memberships_provisioning_index');
            $table->dropColumn(['account_type', 'provisioning_status', 'scim_user_name']);
        });
    }
};
