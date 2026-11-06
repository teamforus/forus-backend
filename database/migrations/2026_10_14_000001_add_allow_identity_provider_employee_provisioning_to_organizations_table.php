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
        Schema::table('organizations', function (Blueprint $table) {
            $table->boolean('allow_identity_provider_employee_provisioning')
                ->default(false)->after('allow_identity_provider_requester_provisioning');

            $table->boolean('allow_identity_provider_employee_provisioning_profiles')
                ->default(false)->after('allow_identity_provider_employee_provisioning');
        });
    }

    /**
     * @return void
     */
    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn([
                'allow_identity_provider_employee_provisioning',
                'allow_identity_provider_employee_provisioning_profiles',
            ]);
        });
    }
};
