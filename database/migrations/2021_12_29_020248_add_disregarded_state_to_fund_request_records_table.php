<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDisregardedStateToFundRequestRecordsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement(
            "ALTER TABLE `fund_request_records` CHANGE `state` `state` ".
            "ENUM('pending', 'approved', 'declined', 'disregarded') DEFAULT 'pending';"
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement(
            "ALTER TABLE `fund_request_records` CHANGE `state` `state` ".
            "ENUM('pending', 'approved', 'declined') DEFAULT 'pending';"
        );
    }
}
