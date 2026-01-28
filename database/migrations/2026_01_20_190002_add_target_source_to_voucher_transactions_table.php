<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('voucher_transactions', function (Blueprint $table) {
            $table->enum('target_source_type', [
                'fund_request',
                'profile_bank_account',
                'reimbursement',
                'voucher_transaction',
            ])->nullable()->after('target_name');
            $table->unsignedBigInteger('target_source_id')->nullable()->after('target_source_type');
            $table->index(['target_source_type', 'target_source_id'], 'voucher_transactions_target_source_index');
        });

        DB::table('voucher_transactions')
            ->whereNotNull('target_reimbursement_id')
            ->update([
                'target_source_type' => 'reimbursement',
                'target_source_id' => DB::raw('target_reimbursement_id'),
            ]);
    }

    public function down(): void
    {
        DB::table('voucher_transactions')
            ->where('target_source_type', 'reimbursement')
            ->whereNotNull('target_source_id')
            ->update(['target_reimbursement_id' => DB::raw('target_source_id')]);

        Schema::table('voucher_transactions', function (Blueprint $table) {
            $table->dropIndex('voucher_transactions_target_source_index');
            $table->dropColumn('target_source_id');
            $table->dropColumn('target_source_type');
        });
    }
};
