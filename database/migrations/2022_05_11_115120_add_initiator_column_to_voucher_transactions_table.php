<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


/**
 * @noinspection PhpUnused
 */
class AddInitiatorColumnToVoucherTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('voucher_transactions', function (Blueprint $table) {
            $table->enum('initiator', ['provider', 'sponsor'])
                ->default('provider')
                ->after('state');
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
            $table->dropColumn('initiator');
        });
    }
}
