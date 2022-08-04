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
        Schema::table('fund_configs', function (Blueprint $table) {
            $table->boolean('show_voucher_qr')->default(true)->after('employee_can_see_product_vouchers');
            $table->boolean('show_voucher_amount')->default(true)->after('show_voucher_qr');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('fund_configs', function (Blueprint $table) {
            $table->dropColumn('show_voucher_qr');
            $table->dropColumn('show_voucher_amount');
        });
    }
};
