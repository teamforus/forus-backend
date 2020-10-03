<?php

use App\Models\BusinessType;
use App\Models\BusinessTypeTranslation;
use Illuminate\Database\Seeder;

class BusinessTypesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        self::seed(true);
    }

    public static function seed($deleteExisting = false): void
    {
        if ($deleteExisting) {
            Schema::disableForeignKeyConstraints();

            BusinessType::query()->forceDelete();
            BusinessTypeTranslation::query()->forceDelete();

            Schema::enableForeignKeyConstraints();
        }

        self::seedFile('business-types-with-ids');
    }

    private static function seedFile($file, bool $service = false): void
    {
        $list = self::loadTaxonomies($file, [
            'nl' => 'nl-NL',
            'en' => 'en-US'
        ], 'en')->toArray();

        $date = now()->format('Y-m-d H:i:s');
        $depth = 1;

        $translations = [];

        $businessTypes = array_values(array_map(static function(
            $category
        ) use ($date, $depth, &$translations, $service) {
            foreach ($category['names'][$depth - 1] as $locale => $name) {
                $translations[] = [
                    'locale' => $locale,
                    'name' => $name,
                    'business_type_id' => $category['id'],
                ];
            }

            return [
                'id' => $category['id'],
                'key' => $category['keys'][$depth - 1],
                'created_at' => $date,
                'updated_at' => $date,
            ];
        }, $list));

        BusinessType::query()->insert($businessTypes);
        BusinessTypeTranslation::query()->insert($translations);
    }

    /**
     * @param string $file
     * @param array $locales
     * @param string $keyLocale
     * @return mixed
     */
    public static function loadTaxonomies(
        string $file,
        array $locales,
        string $keyLocale
    ) {
        $taxonomiesRaw = [];
        $taxonomiesNames = [];

        foreach ($locales as $localeKey => $locale) {
            array_set($taxonomiesRaw, $localeKey, collect(
                explode("\n", file_get_contents(database_path(
                    sprintf('/seeds/db/%s.%s.txt', $file, $locale)
                ))))->filter(function($row) {
                return !empty($row) && !starts_with($row, ['#']);
            })->map(function($row) use ($localeKey, &$taxonomiesNames) {
                list($id, $names) = explode(' - ', $row);

                $names = explode(' > ' , $names);
                $keys = array_map("str_slug", $names);

                if (!isset($taxonomiesNames[$id])) {
                    $taxonomiesNames[$id] = [];
                }

                array_set($taxonomiesNames[$id], $localeKey,  $names);

                return compact('id', 'names', 'keys');
            })->values());
        }

        return $taxonomiesRaw[$keyLocale]->map(function($taxonomy) use ($taxonomiesNames) {
            return array_set($taxonomy, 'names', array_map(static function(
                $nameKey
            ) use ($taxonomiesNames, $taxonomy) {
                return array_map(static function($names) use ($nameKey)  {
                    return $names[$nameKey];
                }, $taxonomiesNames[$taxonomy['id']]);
            }, array_keys($taxonomy['names'])));
        });
    }
}
