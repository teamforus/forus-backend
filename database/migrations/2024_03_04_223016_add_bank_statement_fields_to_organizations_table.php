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
        Schema::table('organizations', function (Blueprint $table) {
            $table->boolean('bank_transaction_id')->default(true)->after('show_provider_transactions');
            $table->boolean('bank_transaction_date')->default(true)->after('bank_transaction_id');
            $table->boolean('bank_branch_number')->default(true)->after('bank_transaction_date');
            $table->boolean('bank_branch_id')->default(true)->after('bank_branch_number');
            $table->boolean('bank_branch_name')->default(true)->after('bank_branch_id');
            $table->boolean('bank_fund_name')->default(true)->after('bank_branch_name');
            $table->boolean('bank_note')->default(true)->after('bank_fund_name');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn('bank_transaction_id');
            $table->dropColumn('bank_transaction_date');
            $table->dropColumn('bank_branch_number');
            $table->dropColumn('bank_branch_id');
            $table->dropColumn('bank_branch_name');
            $table->dropColumn('bank_fund_name');
            $table->dropColumn('bank_note');
        });
    }
};
