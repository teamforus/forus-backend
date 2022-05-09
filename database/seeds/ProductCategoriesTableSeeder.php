<?php

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductCategoryTranslation;

class ProductCategoriesTableSeeder extends DatabaseSeeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        self::seedProducts();
    }

    /**
     * @param false $deleteExisting
     */
    public static function seedProducts($deleteExisting = false): void
    {
        if ($deleteExisting) {
            Schema::disableForeignKeyConstraints();

            ProductCategory::query()->forceDelete();
            ProductCategoryTranslation::query()->forceDelete();

            Schema::enableForeignKeyConstraints();
        }

        self::seedFile('taxonomy-with-ids');
        self::seedFile('services-with-ids');

        $model = new ProductCategory();

        if (Schema::hasColumns($model->getTable(), [$model->getLftName(), $model->getRgtName()])) {
            ProductCategory::fixTree();
        }
    }

    /**
     * @param $file
     */
    private static function seedFile($file): void
    {
        $taxonomies = self::loadTaxonomies($file, [
            'en' => 'en-US',
            'nl' => 'nl-NL'
        ], 'en')->toArray();

        $depth = 1;
        $translations = [];
        $depths = ['categories' => [], 'keys' => []];

        while ($list = self::filterByDepth($taxonomies, $depth)) {
            $parents = $depths['keys'][$depth - 1] ?? [];

            $categories = array_values(array_map(static function ($category) use (
                $depth, $parents, &$translations
            ) {
                $names = $category['names'][$depth - 1];

                foreach ($names as $locale => $name) {
                    $translations[] = [
                        'locale' => $locale,
                        'name' => $name,
                        'product_category_id' => $category['id'],
                    ];
                }

                return [
                    'id' => $category['id'],
                    'key' => $category['keys'][$depth - 1],
                    'parent_id' => $depth > 1 ? $parents[$category['keys'][$depth - 2]] ?? null : null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }, $list));

            $depths['categories'][$depth] = $categories;
            $depths['keys'][$depth] = array_pluck($categories, 'id', 'key');

            $depth++;
        }

        $categories = array_flatten($depths['categories'], 1);

        ProductCategory::query()->insert($categories);
        ProductCategoryTranslation::query()->insert($translations);
    }

    /**
     * @param $list
     */
    public static function migrateProducts($list): void
    {
        foreach ($list as $oldId => $newId) {
            Product::withTrashed()->where('product_category_id', $oldId)->update([
                'product_category_id' => $newId
            ]);
        }
    }

    /**
     * @param array $rows
     * @param int $depth
     * @return array|bool
     */
    public static function filterByDepth(array $rows, int $depth = 1)
    {
        return array_filter($rows, static function ($row) use ($depth) {
            return $row['depth'] === $depth;
        }) ?: false;
    }

    /**
     * @param string $file
     * @param array $locales
     * @param string $keyLocale
     * @return mixed
     */
    public static function loadTaxonomies(string $file, array $locales, string $keyLocale)
    {
        $taxonomiesRaw = [];
        $taxonomiesNames = [];

        foreach ($locales as $localeKey => $locale) {
            $data = file_get_contents(database_path(sprintf('/seeds/db/%s.%s.txt', $file, $locale)));
            $data = explode(PHP_EOL, str_replace("\r\n", PHP_EOL, $data));

            array_set($taxonomiesRaw, $localeKey, collect($data)->filter(function ($row) {
                return !empty($row) && !starts_with($row, ['#']);
            })->map(function ($row) use ($localeKey, &$taxonomiesNames) {
                [$id, $names] = explode(' - ', $row);

                $names = explode(' > ', $names);
                $keys = array_map("str_slug", $names);
                $depth = count($names);

                if (!isset($taxonomiesNames[$id])) {
                    $taxonomiesNames[$id] = [];
                }

                array_set($taxonomiesNames[$id], $localeKey, $names);

                return compact('id', 'names', 'keys', 'depth');
            })->values());
        }

        return $taxonomiesRaw[$keyLocale]->map(function ($taxonomy) use ($taxonomiesNames) {
            return array_set($taxonomy, 'names', array_map(static function ($nameKey) use (
                $taxonomiesNames, $taxonomy
            ) {
                return array_map(static function ($names) use ($nameKey) {
                    return $names[$nameKey];
                }, $taxonomiesNames[$taxonomy['id']]);
            }, array_keys($taxonomy['names'])));
        });
    }
}
