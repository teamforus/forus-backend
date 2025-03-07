<?php

use App\Models\FundProvider;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('fund_providers', function (Blueprint $table) {
            $table->boolean('allow_some_products')->default(false)
                ->after('allow_products');
        });

        FundProvider::get()->each(function (FundProvider $fundProvider) {
            $fundProvider->update([
                'allow_some_products' => $fundProvider->products()->count() > 0,
            ]);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('fund_providers', function (Blueprint $table) {
            $table->dropColumn('allow_some_products');
        });
    }
};
