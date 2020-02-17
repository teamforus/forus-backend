<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Voucher;

class AddEmployeeIdFieldToVouchers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('vouchers', function(Blueprint $table) {
            $table->unsignedInteger('employee_id')->nullable()->after('note');
            $table->foreign('employee_id'
            )->references('id')->on('employees')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::table('vouchers', function(Blueprint $table) {
            $table->dropForeign('vouchers_employee_id_foreign');
            $table->dropColumn('employee_id');
        });
    }
}
