<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * @noinspection PhpUnused
 */
class CreateTableFundLimitMultipliersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('fund_limit_multipliers', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('fund_id')->unsigned();
            $table->string('record_type_key', 200)->nullable();
            $table->integer('multiplier')->unsigned();
            $table->timestamps();

            $table->foreign('record_type_key'
            )->references('key')->on('record_types')->onDelete('set null');

            $table->foreign('fund_id'
            )->references('id')->on('funds')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('fund_limit_multipliers');
    }
}
