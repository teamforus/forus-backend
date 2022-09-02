<?php

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
        Schema::table('fund_top_up_transactions', function(Blueprint $table) {
            $table->string('bunq_transaction_id',200)->nullable()->change();
            $table->renameColumn('bunq_transaction_id', 'bank_transaction_id');
        });

        Schema::table('fund_top_up_transactions', function(Blueprint $table) {
            $table->unsignedBigInteger('bank_connection_account_id')->nullable()
                ->after('bank_transaction_id');

            $table->foreign('bank_connection_account_id')
                ->references('id')->on('bank_connection_accounts')
                ->onDelete('RESTRICT');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('fund_top_up_transactions', function(Blueprint $table) {
            $table->dropForeign('fund_top_up_transactions_bank_connection_account_id_foreign');
            $table->renameColumn('bank_transaction_id', 'bunq_transaction_id');
            $table->dropColumn('bank_connection_account_id');
        });
    }
};
