<?php

namespace Database\Seeders;

use App\Models\ProductCategory;

class ProductCategoriesTableSeeder extends DatabaseSeeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        $categoriesPath = database_path('/seeders/db/product_categories.json');
        $categoriesData = json_decode(file_get_contents($categoriesPath), JSON_OBJECT_AS_ARRAY);

        $this->createCategories($categoriesData);

        ProductCategory::fixTree();

        ProductCategory::whereIsRoot()->each(function (ProductCategory $category) {
            $category->descendants()->update([
                'root_id' => $category->id,
            ]);
        });
    }

    /**
     * @param array $categories
     * @param ProductCategory|null $parent
     * @return void
     */
    protected function createCategories(array $categories, ?ProductCategory $parent = null): void
    {
        foreach ($categories as $category) {
            $model = ProductCategory::create([
                'id' => $category['id'],
                'key' => $category['id'],
                'parent_id' => $parent?->id,
            ]);

            $model->translateOrNew('en')->fill([
                'name' => $category['name']['en'],
            ])->save();

            $model->translateOrNew('nl')->fill([
                'name' => $category['name']['nl'],
            ])->save();

            $this->createCategories($category['children'] ?? [], $model);
        }
    }
}
