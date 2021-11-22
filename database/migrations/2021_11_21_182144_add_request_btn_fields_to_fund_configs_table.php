<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRequestBtnFieldsToFundConfigsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('fund_configs', function (Blueprint $table) {
            $table->string('request_btn_text', 50)->after('allow_blocking_vouchers')->nullable();
            $table->string('request_btn_link', 100)->after('request_btn_text')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('fund_configs', function (Blueprint $table) {
            $table->dropColumn('request_btn_text');
            $table->dropColumn('request_btn_link');
        });
    }
}
