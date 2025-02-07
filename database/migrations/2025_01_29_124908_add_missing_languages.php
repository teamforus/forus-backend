<?php

use Illuminate\Database\Migrations\Migration;
use Database\Seeders\LanguagesTableSeeder;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        (new LanguagesTableSeeder())->run();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void {}
};
