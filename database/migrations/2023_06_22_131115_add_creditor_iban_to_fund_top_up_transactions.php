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
        Schema::table('fund_top_up_transactions', function (Blueprint $table) {
            $table->string('creditor_iban', 200)
                ->nullable()
                ->after('bank_connection_account_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('fund_top_up_transactions', function (Blueprint $table) {
            $table->dropColumn('creditor_iban');
        });
    }
};
