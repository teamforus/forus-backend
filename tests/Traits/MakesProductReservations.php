<?php

namespace Tests\Traits;

use App\Models\Fund;
use App\Models\Organization;
use App\Models\Product;
use App\Models\Traits\HasDbTokens;
use App\Models\Voucher;
use App\Scopes\Builders\ProductQuery;
use App\Scopes\Builders\ProductSubQuery;
use App\Scopes\Builders\VoucherQuery;
use App\Models\ProductReservation;

trait MakesProductReservations
{
    use HasDbTokens;

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

        $this->assertNotNull($product, 'No product suitable for reservation found.');

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