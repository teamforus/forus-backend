<?php

use Database\Seeders\ImplementationsTableSeeder;
use Illuminate\Database\Migrations\Migration;

return new class () extends Migration {
    /**
     * Run the migrations.
     *
     * @throws \Throwable
     * @return void
     */
    public function up(): void
    {
        (new ImplementationsTableSeeder())->run();
    }

    /**
     * Reverse the migrations.
     *
     * @throws Exception
     * @return void
     */
    public function down(): void
    {
    }
};
