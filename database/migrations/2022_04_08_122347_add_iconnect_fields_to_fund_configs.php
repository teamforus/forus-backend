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
        Schema::table('fund_configs', function (Blueprint $table) {
            $table->string('iconnect_target_binding')->nullable()->after('backoffice_fallback');
            $table->string('iconnect_api_oin')->nullable()->after('iconnect_target_binding');
            $table->string('iconnect_base_url')->nullable()->after('iconnect_api_oin');
        });

        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn('iconnect_api_oin');
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
            $table->string('iconnect_api_oin')->after('bsn_enabled')->nullable();
        });

        Schema::table('fund_configs', function (Blueprint $table) {
            $table->dropColumn('iconnect_api_oin', 'iconnect_target_binding', 'iconnect_base_url');
        });
    }
};
