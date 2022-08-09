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
        Schema::table('voucher_transactions', static function (Blueprint $table) {
            $table->unsignedInteger('employee_id')->nullable()->after('organization_id');
            $table->foreign('employee_id'
            )->references('id')->on('employees')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('voucher_transactions', static function (Blueprint $table) {
            $table->dropForeign('voucher_transactions_employee_id_foreign');
            $table->dropColumn('employee_id');
        });
    }
};
