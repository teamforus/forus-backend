<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->boolean('allow_bi_connection')
                ->default(false)
                ->after('allow_fund_request_record_edit');

            $table->enum('bi_connection_auth_type', ['disabled', 'header', 'parameter'])
                ->default('disabled')
                ->after('provider_throttling_value');

            $table->string('bi_connection_token', 200)
                ->after('bi_connection_auth_type');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn('allow_bi_connection');
            $table->dropColumn('bi_connection_auth_type');
            $table->dropColumn('bi_connection_token');
        });
    }
};
