<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('fund_payout_formulas', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('fund_id')->unsigned();
            $table->enum('type', ['multiply', 'fixed']);
            $table->decimal('amount', 10)->unsigned();
            $table->string('record_type_key', 200)->nullable();
            $table->timestamps();

            $table->foreign('record_type_key')
                ->references('key')
                ->on('record_types')
                ->onDelete('restrict');

            $table->foreign('fund_id')
                ->references('id')
                ->on('funds')
                ->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::drop('fund_payout_formulas');
    }
};
