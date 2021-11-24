<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * @noinspection PhpUnused
 */
class AddVoucherTransactionBulkIdToTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('voucher_transactions', function (Blueprint $table) {
            $table->unsignedInteger('voucher_transaction_bulk_id')
                ->after('fund_provider_product_id')
                ->nullable()
                ->index();

            $table->foreign('voucher_transaction_bulk_id')
                ->references('id')->on('voucher_transaction_bulks')
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
        Schema::table('voucher_transactions', function (Blueprint $table) {
            $table->dropForeign('voucher_transactions_voucher_transaction_bulk_id_foreign');
            $table->dropColumn('voucher_transaction_bulk_id');
        });
    }
}
