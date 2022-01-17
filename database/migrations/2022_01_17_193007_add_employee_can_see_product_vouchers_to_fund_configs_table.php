<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * @noinspection PhpUnused
 */
class AddEmployeeCanSeeProductVouchersToFundConfigsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('fund_configs', static function (Blueprint $table) {
            $table->boolean('employee_can_see_product_vouchers')
                ->default(false)
                ->after('allow_blocking_vouchers');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('fund_configs', static function (Blueprint $table) {
            $table->dropColumn('employee_can_see_product_vouchers');
        });
    }
}
