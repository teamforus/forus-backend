<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('fund_provider_product_exclusions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('fund_provider_id');
            $table->unsignedInteger('product_id')->nullable()->default(null);
            $table->timestamps();

            $table->foreign('fund_provider_id')
                ->references('id')->on('fund_providers')->onDelete('cascade');

            $table->foreign('product_id')
                ->references('id')->on('products')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('fund_provider_product_exclusions');
    }
};
