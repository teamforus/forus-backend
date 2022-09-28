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
            $table->boolean('has_external_vouchers')->after('show_voucher_amount')->default(false);
        });

        Fund::whereHas('fund_config', function(Builder $builder) {
            $builder->where('show_voucher_qr', 0);
            $builder->where('show_voucher_amount', 0);
        })->each(function (Fund $fund) {
            $fund->fund_config->update([
                'has_external_vouchers' => true
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
            $table->dropColumn('has_external_vouchers');
        });
    }
};
