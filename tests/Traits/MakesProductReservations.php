<?php

namespace Tests\Traits;

use App\Models\Fund;
use App\Models\FundProvider;
use App\Models\Organization;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Traits\HasDbTokens;
use App\Models\Voucher;
use App\Scopes\Builders\FundProviderQuery;
use App\Scopes\Builders\ProductQuery;
use App\Scopes\Builders\ProductSubQuery;
use App\Scopes\Builders\VoucherQuery;
use App\Models\ProductReservation;
use Illuminate\Foundation\Testing\WithFaker;

trait MakesProductReservations
{
    use HasDbTokens, WithFaker;

    /**
     * @param Organization $organization
     * @param string $fundType
     * @return Voucher
     */
    public function findVoucherForReservation(Organization $organization, string $fundType): Voucher
    {
        $funds = $organization->funds()->where('type', $fundType)->get();
        $this->assertNotNull($funds->count() ?: null);

        /** @var Voucher $voucher */
        $voucher = VoucherQuery::whereNotExpiredAndActive(
            $organization->identity->vouchers()->whereIn('fund_id', $funds->pluck('id'))
        )->whereNull('product_id')->first();

        $this->assertNotNull($voucher, 'No suitable voucher found.');

        return $voucher;
    }

    private function createProductForReservation(Organization $organization): Product
    {
        $product = Product::query()->create([
            'name'                  => $this->faker->text(60),
            'description'           => $this->faker->text(),
            'organization_id'       => $organization->id,
            'product_category_id'   => ProductCategory::first()->id,
            'reservation_enabled'   => 1,
            'expire_at'             => now()->addDays(30),
            'price_type'            => Product::PRICE_TYPE_REGULAR,
            'unlimited_stock'       => 1,
            'price_discount'        => 0,
            'total_amount'          => 0,
            'sold_out'              => 0,
            'price'                 => 20,
        ]);

        foreach ($organization->funds as $fund) {
            $product->fund_providers()->firstOrCreate([
                'organization_id' => $organization->id,
                'fund_id'         => $fund->id,
                'state'           => FundProvider::STATE_ACCEPTED,
                'allow_budget'    => true,
                'allow_products'  => true,
            ]);
        }

        /** @var \Illuminate\Database\Eloquent\Collection|FundProvider[] $fund_providers */
        $fund_providers = FundProviderQuery::whereApprovedForFundsFilter(
            FundProvider::query(),
            $organization->funds()->pluck('id')->toArray()
        )->get();

        foreach ($fund_providers as $fund_provider) {
            $product->fund_provider_products()->create([
                'fund_provider_id' => $fund_provider->id
            ]);
        }

        return $product;
    }

    /**
     * @param Voucher $voucher
     * @return Product
     * @throws \Exception
     */
    public function findProductForReservation(Voucher $voucher): Product
    {
        $product = ProductQuery::approvedForFundsAndActiveFilter(
            ProductSubQuery::appendReservationStats([
                'voucher_id' => $voucher->id,
                'fund_id' => $voucher->fund_id,
                'identity_address' => $voucher->identity_address,
            ]),
            $voucher->fund_id
        );

        if ($voucher->fund->isTypeSubsidy()) {
            $product
                ->where('reservations_subsidy_enabled', true)
                ->where('limit_available', '>', 0);
        } else {
            $product
                ->where('reservations_budget_enabled', true)
                ->where('price', '<=', $voucher->amount_available);
        }

        /** @var Product $product */
        $product = $product->first();

        return $product;
    }

    /**
     * @param Organization $organization
     * @return ProductReservation
     * @throws \Throwable
     */
    public function makeBudgetReservationInDb(Organization $organization): ProductReservation
    {
        $voucher = $this->findVoucherForReservation($organization, Fund::TYPE_BUDGET);
        $product = $this->findProductForReservation($voucher) ?: $this->createProductForReservation($voucher->fund->organization);

        $reservation = $voucher->reserveProduct($product, null, [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'user_note' => '',
        ]);

        if ($reservation->product->autoAcceptsReservations($voucher->fund)) {
            $reservation->acceptProvider();
        }

        return $reservation;
    }
}