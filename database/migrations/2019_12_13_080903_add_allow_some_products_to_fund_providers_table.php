<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use App\Models\FundProvider;

class AddAllowSomeProductsToFundProvidersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('fund_providers', function (Blueprint $table) {
            $table->boolean('allow_some_products')->default(false)
                ->after('allow_products');
        });

        FundProvider::get()->each(function(FundProvider $fundProvider) {
            $fundProvider->update([
                'allow_some_products' => $fundProvider->products()->count() > 0
            ]);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('fund_providers', function (Blueprint $table) {
            $table->dropColumn('allow_some_products');
        });
    }
}
