<?php

namespace Tests\Traits;

use App\Models\Fund;
use App\Models\FundProvider;
use App\Models\Organization;
use App\Models\Product;
use App\Models\Traits\HasDbTokens;
use App\Models\Voucher;
use App\Scopes\Builders\FundProviderQuery;
use App\Scopes\Builders\ProductQuery;
use App\Scopes\Builders\ProductSubQuery;
use App\Scopes\Builders\VoucherQuery;
use App\Models\ProductReservation;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\WithFaker;

trait MakesProductReservations
{
    use WithFaker;
    use HasDbTokens;
    use MakesTestProducts;

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

        if (!$voucher) {
            $voucher = $funds->first()->makeVoucher($organization->identity, [
                'state' => Voucher::STATE_ACTIVE
            ], 10000);
        }

        $this->assertNotNull($voucher, 'No suitable voucher found.');

        return $voucher;
    }

    /**
     * @param Organization $organization
     * @param Collection|Fund[] $funds
     * @return Product
     */
    private function createProductForReservation(Organization $organization, Collection|array $funds): Product
    {
        $product = $this->makeTestProduct($organization);

        foreach ($funds as $fund) {
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
            collect($funds)->pluck('id')->toArray()
        )->get();

        foreach ($fund_providers as $fund_provider) {
            $product->fund_provider_products()->create([
                'amount' => $product->price,
                'limit_total' => $product->unlimited_stock ? 1000 : $product->stock_amount,
                'fund_provider_id' => $fund_provider->id,
                'limit_per_identity' => $product->unlimited_stock ? 25 : ceil(max($product->stock_amount / 10, 1)),
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
                'identity_id' => $voucher->identity_id,
                'voucher_id' => $voucher->id,
                'fund_id' => $voucher->fund_id,
            ]),
            $voucher->fund_id
        )->where('limit_total_available', '>', 0);

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

        if (!$product) {
            return $this->createProductForReservation($voucher->fund->organization, [$voucher->fund]);
        }

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
        $product = $this->findProductForReservation($voucher);

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