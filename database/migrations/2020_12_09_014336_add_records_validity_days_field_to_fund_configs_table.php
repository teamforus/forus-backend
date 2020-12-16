<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Class AddRecordsValidityDaysFieldToFundConfigsTable
 * @noinspection PhpUnused
 */
class AddRecordsValidityDaysFieldToFundConfigsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('fund_configs', static function (Blueprint $table) {
            $table->unsignedMediumInteger('records_validity_days')->nullable()->after('key');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('fund_configs', static function (Blueprint $table) {
            $table->dropColumn('records_validity_days');
        });
    }
}
