<?php

use App\Models\FundConfig;
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
        $enabled = FundConfig::where('limit_generator_amount', true)->pluck('id')->all();
        $disabled = FundConfig::where('limit_generator_amount', false)->pluck('id')->all();

        Schema::table('fund_configs', function (Blueprint $table) {
            $table->decimal('limit_generator_amount',10, 2)
                ->default(config('forus.funds.max_sponsor_voucher_amount'))
                ->change();

            $table->decimal('limit_voucher_top_up_amount',10, 2)
                ->nullable()
                ->after('iconnect_base_url');
        });

        FundConfig::whereIn('id', $enabled)->update([
            'limit_generator_amount' => config('forus.funds.max_sponsor_voucher_amount')
        ]);

        FundConfig::whereIn('id', $disabled)->update([
            'limit_generator_amount' => null
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('fund_configs', function (Blueprint $table) {
            $table->boolean('limit_generator_amount')->default(true)->change();
            $table->dropColumn('limit_voucher_top_up_amount');
        });
    }
};
