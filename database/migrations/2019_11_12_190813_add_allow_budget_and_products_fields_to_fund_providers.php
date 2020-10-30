<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use App\Models\FundProvider;

class AddAllowBudgetAndProductsFieldsToFundProviders extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('fund_providers', function(Blueprint $table) {
            $table->boolean('allow_budget')->default(false)->after('fund_id');
            $table->boolean('allow_products')->default(false)->after('allow_budget');
            $table->boolean('dismissed')->default(false)->after('allow_products');
        });

        // approved providers
        FundProvider::where([
            'state' => 'approved'
        ])->update([
            'allow_budget' => true,
            'allow_products' => true,
        ]);

        // declined providers
        FundProvider::where([
            'state' => 'declined'
        ])->update([
            'dismissed' => true,
        ]);
        Schema::table('fund_providers', function(Blueprint $table) {
            $table->dropColumn('state');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('fund_providers', function(Blueprint $table) {
            $table->dropColumn('allow_budget');
            $table->dropColumn('allow_products');
        });
    }
}
