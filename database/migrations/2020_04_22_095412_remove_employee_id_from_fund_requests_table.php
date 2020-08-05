<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveEmployeeIdFromFundRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('fund_requests', function (Blueprint $table) {
            $table->dropColumn('employee_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('fund_requests', function (Blueprint $table) {
            $table->unsignedInteger('employee_id')->nullable()->after('note');
        });
    }
}
