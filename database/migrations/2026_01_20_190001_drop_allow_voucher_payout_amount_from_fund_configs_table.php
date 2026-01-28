<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $timestamp = now();

        foreach (DB::table('fund_configs')
            ->whereNotNull('allow_voucher_payout_amount')
            ->get(['fund_id', 'allow_voucher_payout_amount']) as $fundConfig) {

            if (!$fundConfig->fund_id) {
                continue;
            }

            DB::table('fund_payout_formulas')->insert([
                'fund_id' => $fundConfig->fund_id,
                'type' => 'fixed',
                'amount' => $fundConfig->allow_voucher_payout_amount,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ]);
        }

        Schema::table('fund_configs', function (Blueprint $table) {
            $table->dropColumn('allow_voucher_payout_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fund_configs', function (Blueprint $table) {
            $table
                ->decimal('allow_voucher_payout_amount', 10)
                ->nullable()
                ->after('allow_voucher_payouts');
        });
    }
};
