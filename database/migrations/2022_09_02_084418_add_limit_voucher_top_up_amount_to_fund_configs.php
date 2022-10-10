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
        $disabled = FundConfig::where('limit_generator_amount', false)->pluck('id')->all();
        $defaultVoucherLimit = 5000;

        Schema::table('fund_configs', function (Blueprint $table) use ($defaultVoucherLimit) {
            $table->boolean('allow_voucher_top_ups')
                ->default(false)
                ->after('allow_direct_payments');

            $table->decimal('limit_generator_amount',10)
                ->default($defaultVoucherLimit)
                ->nullable()
                ->change();

            $table->decimal('limit_voucher_top_up_amount',10)
                ->default($defaultVoucherLimit)
                ->nullable()
                ->after('limit_generator_amount');

            $table->decimal('limit_voucher_total_amount',10)
                ->default($defaultVoucherLimit)
                ->nullable()
                ->after('limit_voucher_top_up_amount');
        });

        FundConfig::whereIn('id', $disabled)->update([
            'limit_generator_amount' => null,
        ]);

        FundConfig::query()->update([
            'limit_generator_amount' => $defaultVoucherLimit,
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
            $table->dropColumn('allow_voucher_top_ups');
            $table->boolean('limit_generator_amount')->default(true)->change();
            $table->dropColumn('limit_voucher_top_up_amount');
            $table->dropColumn('limit_voucher_total_amount');
        });
    }
};
