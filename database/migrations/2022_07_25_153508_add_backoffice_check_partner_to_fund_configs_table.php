<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * @noinspection PhpUnused
 */
class AddBackofficeCheckPartnerToFundConfigsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('fund_configs', function (Blueprint $table) {
            $table->boolean('backoffice_check_partner')->default(false)->after('backoffice_enabled');
            $table->dropColumn('backoffice_status');
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
            $table->boolean('backoffice_status')->default(false)->after('backoffice_enabled');
            $table->dropColumn('backoffice_check_partner');
        });
    }
}
