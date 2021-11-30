<?php

use Illuminate\Database\Migrations\Migration;

class SeedNotificationTemplatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     * @throws Exception
     */
    public function up()
    {
        (new NotificationTemplatesTableSeeder())->run();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void {}
}
