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
        Schema::table('identity_provider_scim_credentials', function (Blueprint $table) {
            $table->enum('account_type', ['employee', 'requester'])->after('connection_id');
        });
    }

    /**
     * @return void
     */
    public function down(): void
    {
        Schema::table('identity_provider_scim_credentials', function (Blueprint $table) {
            $table->dropColumn('account_type');
        });
    }
};
