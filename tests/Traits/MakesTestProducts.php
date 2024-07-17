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
}