<?php

namespace Tests\Traits;

use App\Models\Organization;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Str;

trait MakesTestProducts
{
    use WithFaker;

    /**
     * @param Organization $organization
     * @param float $price
     * @param int|null $product_category_id
     * @return Product
     */
    public function makeTestProduct(
        Organization $organization,
        float $price = 10,
        ?int $product_category_id = null,
    ): Product {
        return $organization->products()->forceCreate([
            'name' => $this->faker->text(60),
            'description' => $this->faker->text(),
            'price' => $price,
            'total_amount' => 10,
            'sold_out' => false,
            'expire_at' => now()->addYear(),
            'product_category_id' => $product_category_id ?: ProductCategory::inRandomOrder()->first()->id,
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
     * @param float $price
     * @return array|Product[]
     */
    public function makeTestProducts(Organization $organization, int $count = 1, float $price = 10): array
    {
        return array_map(fn () => $this->makeTestProduct($organization, $price), range(1, $count));
    }

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
     * @return ProductCategory
     */
    protected function makeProductCategory(): ProductCategory
    {
        $name = $this->faker->sentence(5);

        $category = ProductCategory::create([
            'key' => Str::slug($name),
        ]);

        $category->translateOrNew(app()->getLocale())->fill([
            'name' => $name,
        ])->save();

        return $category;
    }
}
