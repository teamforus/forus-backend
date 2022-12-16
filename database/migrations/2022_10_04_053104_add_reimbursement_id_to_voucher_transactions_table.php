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
        Schema::table('voucher_transactions', function (Blueprint $table) {
            $table->unsignedBigInteger('reimbursement_id')
                ->after('employee_id')
                ->nullable();

            $table->foreign('reimbursement_id')
                ->references('id')->on('reimbursements')
                ->onDelete('cascade');
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
            $table->dropForeign('voucher_transactions_reimbursement_id_foreign');
            $table->dropColumn('reimbursement_id');
        });
    }
};
