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

trait ProductReservationData
{
    use HasDbTokens;

    /**
     * @param Organization $organization
     * @param string $fundType
     * @return Voucher
     */
    public function getVoucherForFundType(Organization $organization, string $fundType): Voucher
    {
        /** @var Fund $fund */
        $fund = $organization->funds()->where('type', $fundType)->first();
        $this->assertNotNull($fund);

        /** @var Voucher $voucher */
        $voucher = VoucherQuery::whereNotExpiredAndActive(
            $organization->identity->vouchers()->where('fund_id', $fund->id)
        )->whereNull('product_id')->first();

        $this->assertNotNull($voucher);

        return $voucher;
    }

    /**
     * @param Voucher $voucher
     * @param string $identity_address
     * @param string $fundType
     * @return Product
     * @throws \Exception
     */
    public function getProductForFundType(
        Voucher $voucher,
        string $identity_address,
        string $fundType
    ): Product {
        $product = ProductQuery::approvedForFundsAndActiveFilter(
            ProductSubQuery::appendReservationStats([
                'voucher_id' => $voucher->id,
                'fund_id' => $voucher->fund_id,
                'identity_address' => $identity_address,
            ]),
            $voucher->fund_id
        );

        if ($fundType === Fund::TYPE_SUBSIDIES) {
            $product->where('reservations_subsidy_enabled', true)
                ->where('limit_per_identity', '>', 0);
        } else {
            $product->where('reservations_budget_enabled', true)
                ->where('price', '<=', $voucher->amount_available);
        }

        /** @var Product $product */
        $product = $product->first();

        $this->assertNotNull($product);

        return $product;
    }

    /**
     * @param Organization $organization
     * @return ProductReservation
     * @throws \Throwable
     */
    public function makeReservationInDb(Organization $organization): ProductReservation
    {
        $voucher = $this->getVoucherForFundType($organization, Fund::TYPE_BUDGET);
        $product = $this->getProductForFundType($voucher, $organization->identity->address, Fund::TYPE_BUDGET);

        $reservation = $voucher->reserveProduct($product, null, [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'user_note' => '',
        ]);

        $this->assertNotNull($product);

        if ($reservation->product->autoAcceptsReservations($voucher->fund)) {
            $reservation->acceptProvider();
        }

        return $reservation;
    }
}