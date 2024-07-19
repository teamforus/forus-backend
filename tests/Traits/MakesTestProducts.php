<?php

namespace Tests\Traits;

use App\Models\Organization;
use App\Models\Product;
use App\Models\ProductCategory;

trait MakesTestProducts
{
    public function makeTestProduct(Organization $organization): Product
    {
        return $organization->products()->forceCreate([
            'name' => $this->faker->text(60),
            'description' => $this->faker->text(),
            'price' => 10,
            'total_amount' => 10,
            'sold_out' => false,
            'expire_at' => now()->addYear(),
            'product_category_id' => ProductCategory::inRandomOrder()->first()->id,
            'organization_id' => $organization->id,
            'unlimited_stock' => false,
            'price_type' => Product::PRICE_TYPE_REGULAR,
            'price_discount' => 0,
            'reservation_enabled' => 1,
        ]);
    }

    /**
     * @param Organization $organization
     * @param int $count
     * @return array|Product[]
     */
    public function makeTestProducts(Organization $organization, int $count = 1): array
    {
        return array_map(fn () => $this->makeTestProduct($organization), range(1, $count));
    }
}