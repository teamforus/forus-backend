<?php

use Illuminate\Database\Migrations\Migration;
use Database\Seeders\ImplementationsTableSeeder;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     * @throws \Throwable
     */
    public function up(): void
    {
        (new ImplementationsTableSeeder())->run();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     * @throws Exception
     */
    public function down(): void {}
};
