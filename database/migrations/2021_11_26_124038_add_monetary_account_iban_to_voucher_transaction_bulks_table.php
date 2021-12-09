<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * @noinspection PhpUnused
 */
class AddMonetaryAccountIbanToVoucherTransactionBulksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('voucher_transaction_bulks', function (Blueprint $table) {
            $table->string('monetary_account_iban', 200)->after('monetary_account_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('voucher_transaction_bulks', function (Blueprint $table) {
            $table->dropColumn('monetary_account_iban');
        });
    }
}
