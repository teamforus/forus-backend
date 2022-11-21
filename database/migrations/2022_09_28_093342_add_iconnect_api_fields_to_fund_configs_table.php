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
            $table->string('iconnect_env', 50)->after('iconnect_base_url')->default('sandbox');
            $table->text('iconnect_key')->after('iconnect_env');
            $table->text('iconnect_key_pass')->after('iconnect_key');
            $table->text('iconnect_cert')->after('iconnect_key_pass');
            $table->text('iconnect_cert_pass')->after('iconnect_cert');
            $table->text('iconnect_cert_trust')->after('iconnect_cert_pass');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('fund_configs', function (Blueprint $table) {
            $table->dropColumn('iconnect_env');
            $table->dropColumn('iconnect_key');
            $table->dropColumn('iconnect_key_pass');
            $table->dropColumn('iconnect_cert');
            $table->dropColumn('iconnect_cert_pass');
            $table->dropColumn('iconnect_cert_trust');
        });
    }
};
