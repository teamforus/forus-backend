<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * @noinspection PhpUnused
 */
class MigrateTopUpTransactions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        DB::table('fund_top_ups')->where('state', '=', 'pending')->delete();
        DB::table('fund_top_ups')->get()->each(function($fund_top_up) {
            DB::table('fund_top_up_transactions')->insert([
                'fund_top_up_id' => $fund_top_up->id,
                'bunq_transaction_id' => $fund_top_up->bunq_transaction_id,
                'amount' => $fund_top_up->amount,
                'updated_at' => $fund_top_up->updated_at,
                'created_at' => $fund_top_up->updated_at
            ]);
        });

        Schema::table('fund_top_ups', function(Blueprint $table) {
            $table->dropColumn(['state', 'amount', 'bunq_transaction_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('fund_top_ups', function(Blueprint $table) {
            $table->float('amount')->unsigned()->nullable()->after('fund_id');
            $table->string('bunq_transaction_id',20)->nullable()->after('amount');
            $table->string('state')->default("pending")->after('bunq_transaction_id');
        });

        DB::table('fund_top_up_transactions')->get()->groupBy('fund_top_up_id')->each(function ($group) {
            $group = collect($group);
            $transaction = $group->shift();

            DB::table('fund_top_ups')->where('id', $transaction->fund_top_up_id)->update([
                'bunq_transaction_id' => $transaction->bunq_transaction_id,
                'state' => 'confirmed',
                'amount' => $transaction->amount,
                'updated_at' => $transaction->updated_at,
                'created_at' => $transaction->created_at
            ]);

            while ($group->count() > 0) {
                $transaction = $group->shift();

                DB::table('fund_top_ups')->insert([
                    'code' => \App\Models\FundTopUp::generateCode(),
                    'fund_id' => DB::table('fund_top_ups')->where([
                        'id' => $transaction->fund_top_up_id
                    ])->first()->fund_id,
                    'bunq_transaction_id' => $transaction->bunq_transaction_id,
                    'amount' => $transaction->amount,
                    'state' => 'confirmed',
                    'updated_at' => $transaction->updated_at,
                    'created_at' => $transaction->created_at,
                ]);
            }
        });

        DB::table('fund_top_up_transactions')->whereNotNull('id')->delete();
    }
}
