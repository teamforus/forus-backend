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
            $table->text('backoffice_client_cert')->after('backoffice_certificate');
            $table->text('backoffice_client_cert_key')->after('backoffice_client_cert');
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
            $table->dropColumn('backoffice_client_cert');
            $table->dropColumn('backoffice_client_cert_key');
        });
    }
};
