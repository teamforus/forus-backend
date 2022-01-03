<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDisregardedStateToFundRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement(
            "ALTER TABLE `fund_requests` CHANGE `state` `state` ".
            "ENUM('pending', 'approved', 'declined', 'approved_partly', 'disregarded') DEFAULT 'pending';"
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
            "ALTER TABLE `fund_requests` CHANGE `state` `state` ".
            "ENUM('pending', 'approved', 'declined', 'approved_partly') DEFAULT 'pending';"
        );
    }
}
