<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Class RemoveSubtractTransactionCostsFromFundConfigsTable
 * @noinspection PhpUnused
 */
class RemoveSubtractTransactionCostsFromFundConfigsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('fund_configs', function (Blueprint $table) {
            $table->dropColumn('subtract_transaction_costs');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('fund_configs', function (Blueprint $table) {
            $table->boolean('subtract_transaction_costs')->default(0)->after('csv_primary_key');
        });
    }
}
