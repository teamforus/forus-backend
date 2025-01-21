<?php

namespace Tests\Traits;

use App\Models\Organization;
use App\Models\Product;
use App\Models\ProductCategory;

trait MakesTestProducts
{
    /**
     * @param Organization $providerOrganization
     * @return Product
     */
    private function makeTestProductForReservation(Organization $providerOrganization): Product
    {
        return Product::create([
            'name' => $this->faker->text(60),
            'description' => $this->faker->text(),
            'organization_id' => $providerOrganization->id,
            'product_category_id' => ProductCategory::first()->id,
            'reservation_enabled' => 1,
            'expire_at' => now()->addDays(30),
            'price_type' => Product::PRICE_TYPE_REGULAR,
            'unlimited_stock' => 1,
            'price_discount' => 0,
            'total_amount' => 0,
            'sold_out' => 0,
            'price' => 120,
            'reservation_extra_payments' => Product::RESERVATION_EXTRA_PAYMENT_GLOBAL,
        ]);
    }

    /**
     * @param Organization $organization
     * @param array $attributes
     * @return Product
     */
    public function makeTestProduct(Organization $organization, array $attributes = []): Product
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
            ...$attributes,
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