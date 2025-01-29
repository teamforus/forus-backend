<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
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
        $languages = LanguagesTableSeeder::LANGUAGES;

        foreach ($languages as $language) {
            if (DB::table('languages')->where('locale', $language['locale'])->doesntExist()) {
                DB::table('languages')->insert($language);
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void {}
};
