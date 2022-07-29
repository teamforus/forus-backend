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
            $table->string('backoffice_client_certificate', 8000)->after('backoffice_certificate');
            $table->string('backoffice_client_certificate_pass', 200)->after('backoffice_client_certificate');
            $table->string('backoffice_client_certificate_key', 8000)->after('backoffice_client_certificate_pass');
            $table->string('backoffice_client_certificate_key_pass', 200)->after('backoffice_client_certificate_key');
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
            $table->dropColumn('backoffice_client_certificate');
            $table->dropColumn('backoffice_client_certificate_pass');
            $table->dropColumn('backoffice_client_certificate_key');
            $table->dropColumn('backoffice_client_certificate_key_pass');
        });
    }
}
