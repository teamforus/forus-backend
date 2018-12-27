<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class FundConfigsSubstractTransactionCosts extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('fund_configs', function (Blueprint $table) {
            $table->boolean('subtract_transaction_costs')->default(0)->after('formula_multiplier');
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
            $table->dropColumn('subtract_transaction_costs');
        });
    }
}
