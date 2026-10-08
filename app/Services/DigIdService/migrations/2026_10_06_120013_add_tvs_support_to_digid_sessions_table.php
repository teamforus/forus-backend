<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('digid_sessions', function (Blueprint $table) {
            $table->enum('connection_type', ['cgi', 'saml', 'tvs'])->change();

            $table->enum('state', [
                'created', 'pending_authorization', 'authorized', 'expired', 'error', 'canceled',
            ])->default('created')->change();

            $table->string('digid_error_code', 20)->nullable()->change();

            $table->string('request_id')->nullable()->unique();
            $table->string('service_uuid', 255)->nullable();
            $table->string('dv_entity_id', 255)->nullable();

            $table->index('session_uid');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('digid_sessions', function (Blueprint $table) {
            $table->dropIndex(['session_uid']);
            $table->dropUnique(['request_id']);
            $table->dropColumn(['request_id', 'service_uuid', 'dv_entity_id']);
        });
    }
};
