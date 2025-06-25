<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('fund_provider_products', function (Blueprint $table) {
            $table->enum('payment_type', ['budget', 'subsidy'])
                ->default('budget')
                ->after('product_id');

            $table->boolean('allow_scanning')
                ->default(true)
                ->after('payment_type');
        });

        $fundIds = DB::table('funds')
            ->where('type', 'subsidies')
            ->pluck('id');

        $fundProviderIds = DB::table('fund_providers')
            ->whereIn('fund_id', $fundIds)
            ->pluck('id');

        DB::table('fund_provider_products')
            ->whereIn('fund_provider_id', $fundProviderIds)
            ->update(['payment_type' => 'subsidy']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fund_provider_products', function (Blueprint $table) {
            $table->dropColumn('payment_type');
            $table->dropColumn('allow_scanning');
        });
    }
};
