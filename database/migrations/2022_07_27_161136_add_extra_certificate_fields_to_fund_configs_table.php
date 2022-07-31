<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddExtraCertificateFieldsToFundConfigsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('fund_configs', function (Blueprint $table) {
            $table->string('backoffice_client_cert', 2000)->after('backoffice_certificate');
            $table->string('backoffice_client_cert_key', 4000)->after('backoffice_client_cert');
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
}
