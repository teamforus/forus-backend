<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIbanFieldsToVoucherTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('voucher_transactions', function (Blueprint $table) {
            $table->string('iban_from', 200)->nullable()->after('amount');
            $table->string('iban_to', 200)->nullable()->after('iban_from');
            $table->timestamp('payment_time')->nullable()->after('iban_to');
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
            $table->dropColumn('iban_from');
            $table->dropColumn('iban_to');
            $table->dropColumn('payment_time');
        });
    }
}
