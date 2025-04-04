<?php

use App\Models\FundProviderProduct;
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
        Schema::table('fund_provider_products', function (Blueprint $table) {
            $table->boolean('limit_total_unlimited')->default(0)->after('limit_total');
        });

        FundProviderProduct::where('limit_total', 999999)->update([
            'limit_total_unlimited' => 1,
            'limit_total' => 0,
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        FundProviderProduct::where('limit_total_unlimited', 1)->update([
            'limit_total' => 999999,
        ]);

        Schema::table('fund_provider_products', function (Blueprint $table) {
            $table->dropColumn('limit_total_unlimited');
        });
    }
};
