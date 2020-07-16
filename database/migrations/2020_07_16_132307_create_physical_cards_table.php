<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePhysicalCardsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('physical_cards', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('voucher_id');
            $table->string('physical_card_code', 50)->nullable();

            $table->timestamps();

            $table->foreign('voucher_id')->references('id')
                ->on('vouchers')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('physical_cards');
    }
}
