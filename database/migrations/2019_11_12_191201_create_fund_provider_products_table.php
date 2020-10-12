<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFundProviderProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('fund_provider_products', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('fund_provider_id');
            $table->unsignedInteger('product_id');
            // $table->unsignedInteger('organization_id');
            // $table->unsignedInteger('fund_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('fund_provider_products');
    }
}
