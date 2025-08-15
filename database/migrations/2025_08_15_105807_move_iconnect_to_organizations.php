<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->string('iconnect_target_binding')->nullable()->after('bank_separator');
            $table->string('iconnect_api_oin')->nullable()->after('iconnect_target_binding');
            $table->string('iconnect_base_url')->nullable()->after('iconnect_api_oin');
            $table->string('iconnect_env', 50)->after('iconnect_base_url')->default('sandbox');
            $table->text('iconnect_key')->after('iconnect_env');
            $table->text('iconnect_key_pass')->after('iconnect_key');
            $table->text('iconnect_cert')->after('iconnect_key_pass');
            $table->text('iconnect_cert_pass')->after('iconnect_cert');
            $table->text('iconnect_cert_trust')->after('iconnect_cert_pass');
        });

        Schema::table('fund_configs', function (Blueprint $table) {
            $table->dropColumn(
                'iconnect_api_oin',
                'iconnect_target_binding',
                'iconnect_base_url',
                'iconnect_env',
                'iconnect_key',
                'iconnect_key_pass',
                'iconnect_cert',
                'iconnect_cert_pass',
                'iconnect_cert_trust',
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn(
                'iconnect_api_oin',
                'iconnect_target_binding',
                'iconnect_base_url',
                'iconnect_env',
                'iconnect_key',
                'iconnect_key_pass',
                'iconnect_cert',
                'iconnect_cert_pass',
                'iconnect_cert_trust',
            );
        });

        Schema::table('fund_configs', function (Blueprint $table) {
            $table->string('iconnect_target_binding')->nullable()->after('backoffice_fallback');
            $table->string('iconnect_api_oin')->nullable()->after('iconnect_target_binding');
            $table->string('iconnect_base_url')->nullable()->after('iconnect_api_oin');
            $table->string('iconnect_env', 50)->after('iconnect_base_url')->default('sandbox');
            $table->text('iconnect_key')->after('iconnect_env');
            $table->text('iconnect_key_pass')->after('iconnect_key');
            $table->text('iconnect_cert')->after('iconnect_key_pass');
            $table->text('iconnect_cert_pass')->after('iconnect_cert');
            $table->text('iconnect_cert_trust')->after('iconnect_cert_pass');
        });
    }
};
