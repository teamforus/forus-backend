<?php

use Illuminate\Database\Migrations\Migration;

/**
 * @noinspection PhpUnused
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement(
            "ALTER TABLE `announcements` CHANGE `scope` `scope` ".
            "ENUM('dashboards', 'sponsor', 'provider', 'validator', 'webshop') DEFAULT 'sponsor';"
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
            "ALTER TABLE `announcements` CHANGE `scope` `scope` ".
            "ENUM('dashboards', 'sponsor', 'provider', 'validator') DEFAULT 'sponsor';"
        );
    }
};
