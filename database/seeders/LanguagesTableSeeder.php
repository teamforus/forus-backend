<?php

namespace Database\Seeders;

use App\Models\Language;

class LanguagesTableSeeder extends DatabaseSeeder
{
    /**
     * The list of languages.
     */
    public const array LANGUAGES = [
        ['name' => 'Dutch', 'locale' => 'nl', 'base' => true],
        ['name' => 'English', 'locale' => 'en'],
        ['name' => 'Polish', 'locale' => 'pl'],
        ['name' => 'Arabic', 'locale' => 'ar'],
        ['name' => 'Turkish', 'locale' => 'tr'],
        ['name' => 'German', 'locale' => 'de'],
        ['name' => 'Ukrainian', 'locale' => 'uk'],
        ['name' => 'Russian', 'locale' => 'ru'],
        ['name' => 'French', 'locale' => 'fr'],
    ];

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        foreach (self::LANGUAGES as $language) {
            if (!Language::where('locale', $language['locale'])->exists()) {
                Language::create($language);
            }
        }

        Language::whereNotNull('locale')->update([
            'base' => false,
        ]);

        Language::where('locale', 'nl')->update([
            'base' => true,
        ]);
    }
}
