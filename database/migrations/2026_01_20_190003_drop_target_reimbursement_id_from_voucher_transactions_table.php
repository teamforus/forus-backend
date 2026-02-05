<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('voucher_transactions', 'target_reimbursement_id')) {
            return;
        }

        Schema::table('voucher_transactions', function (Blueprint $table) {
            $table->dropForeign('voucher_transactions_target_reimbursement_id_foreign');
            $table->dropColumn('target_reimbursement_id');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('voucher_transactions', 'target_reimbursement_id')) {
            return;
        }

        Schema::table('voucher_transactions', function (Blueprint $table) {
            $table->unsignedBigInteger('target_reimbursement_id')
                ->after('target_name')
                ->nullable();

            $table->foreign('target_reimbursement_id')
                ->references('id')
                ->on('reimbursements')
                ->onDelete('cascade');
        });

        DB::table('voucher_transactions')
            ->where('target_source_type', 'reimbursement')
            ->whereNotNull('target_source_id')
            ->update(['target_reimbursement_id' => DB::raw('target_source_id')]);
    }
};
