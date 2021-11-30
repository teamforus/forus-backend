<?php

use Illuminate\Database\Migrations\Migration;

/** @noinspection PhpUnused */
class SeedSystemNotificationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        (new SystemNotificationsTableSeeder())->run();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void {}
}
