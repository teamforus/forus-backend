<?php

use Database\Seeders\NotificationTemplatesTableSeeder;
use Illuminate\Database\Migrations\Migration;

return new class () extends Migration {
    /**
     * Run the migrations.
     *
     * @throws Exception
     * @return void
     */
    public function up(): void
    {
        (new NotificationTemplatesTableSeeder())->run();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
    }
};
