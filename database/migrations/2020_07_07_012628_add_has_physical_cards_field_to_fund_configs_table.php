<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddHasPhysicalCardsFieldToFundConfigsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('fund_configs', function (Blueprint $table) {
            $table->boolean('allow_physical_cards')->default(false)
                ->after('subtract_transaction_costs');
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
            $table->dropColumn('allow_physical_cards');
        });
    }
}
