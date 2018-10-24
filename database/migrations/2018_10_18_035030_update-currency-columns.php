<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateCurrencyColumns extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('fund_top_ups', function (Blueprint $table) {
            $table->decimal('amount',10, 2)->change();
        });

        Schema::table('voucher_transactions', function (Blueprint $table) {
            $table->decimal('amount',10, 2)->change();
        });

        Schema::table('vouchers', function (Blueprint $table) {
            $table->decimal('amount',10, 2)->change();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->decimal('price',10, 2)->change();
            $table->decimal('old_price',10, 2)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {

    }
}
