<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::dropIfExists('fund_sponsor_investments');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::create('fund_sponsor_investments', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('fund_id')->unsigned();
            $table->integer('organization_id')->unsigned();
            $table->float('amount')->unsigned();
            $table->string('state')->default('');
            $table->timestamps();

            $table->foreign('fund_id'
            )->references('id')->on('funds')->onDelete('cascade');

            $table->foreign('organization_id'
            )->references('id')->on('organizations')->onDelete('cascade');
        });
    }
};
