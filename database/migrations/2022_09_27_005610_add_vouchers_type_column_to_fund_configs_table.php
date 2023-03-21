<?php

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Fund;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('fund_configs', function (Blueprint $table) {
            $table->enum('vouchers_type', ['internal', 'external'])
                ->after('show_voucher_amount')
                ->default('internal');
        });

        Fund::whereHas('fund_config', function(Builder $builder) {
            $builder->where('show_voucher_qr', 0);
            $builder->where('show_voucher_amount', 0);
        })->each(function (Fund $fund) {
            $fund->fund_config->update([
                'vouchers_type' => 'external',
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
        Schema::table('fund_configs', function (Blueprint $table) {
            $table->dropColumn('vouchers_type');
        });
    }
};
