<?php

namespace Database\Seeders;

use App\Models\Language;

class LanguagesTableSeeder extends DatabaseSeeder
{
    /**
     * The list of languages.
     */
    public const array LANGUAGES = [
        ['name' => 'Nederlands', 'locale' => 'nl', 'base' => true],
        ['name' => 'English', 'locale' => 'en'],
        ['name' => 'Polski', 'locale' => 'pl'],
        ['name' => 'عربي', 'locale' => 'ar'],
        ['name' => 'Türkçe', 'locale' => 'tr'],
        ['name' => 'Deutsch', 'locale' => 'de'],
        ['name' => 'Українська', 'locale' => 'uk'],
        ['name' => 'Русский', 'locale' => 'ru'],
        ['name' => 'Français', 'locale' => 'fr'],
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
