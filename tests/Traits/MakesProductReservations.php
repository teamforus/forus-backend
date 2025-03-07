<?php

namespace Tests\Traits;

use App\Models\Fund;
use App\Models\FundProvider;
use App\Models\Organization;
use App\Models\Product;
use App\Models\ProductReservation;
use App\Models\Traits\HasDbTokens;
use App\Models\Voucher;
use App\Scopes\Builders\FundProviderQuery;
use App\Scopes\Builders\ProductQuery;
use App\Scopes\Builders\ProductSubQuery;
use App\Scopes\Builders\VoucherQuery;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\WithFaker;
use Throwable;

trait MakesProductReservations
{
    use WithFaker;
    use HasDbTokens;
    use MakesTestProducts;

    /**
     * @var array
     */
    protected array $productReservationResourceStructure = [
        'id',
        'state',
        'state_locale',
        'amount',
        'code',
        'first_name',
        'last_name',
        'user_note',
        'created_at',
        'created_at_locale',
        'accepted_at',
        'accepted_at_locale',
        'rejected_at',
        'rejected_at_locale',
        'canceled_at',
        'canceled_at_locale',
        'expire_at',
        'expire_at_locale',
        'expired',
        'product',
        'fund',
        'voucher_transaction',
        'price',
        'price_locale',
    ];

    /**
     * @param Organization $organization
     * @param string $fundType
     * @return Voucher
     */
    public function findVoucherForReservation(Organization $organization, string $fundType): Voucher
    {
        $funds = $organization->funds()->where('type', $fundType)->get();
        $this->assertNotNull($funds->count() ?: null);

        $voucher = VoucherQuery::whereNotExpiredAndActive(
            $organization->identity->vouchers()->whereIn('fund_id', $funds->pluck('id'))
        )->whereNull('product_id')->first();

        if (!$voucher) {
            $voucher = $funds->first()->makeVoucher($organization->identity, [
                'state' => Voucher::STATE_ACTIVE,
            ], 10000);
        }

        $this->assertNotNull($voucher, 'No suitable voucher found.');

        return $voucher;
    }

    /**
     * @param Voucher $voucher
     * @throws Exception
     * @return Product
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
     * @throws Throwable
     * @return ProductReservation
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

    /**
     * @param Voucher $voucher
     * @param Product $product
     * @return ProductReservation
     */
    public function makeReservation(Voucher $voucher, Product $product): ProductReservation
    {
        $response = $this->makeReservationStoreRequest($voucher, $product, [
            'first_name' => '',
            'last_name' => '',
            'user_note' => [],
        ]);

        $response->assertJsonValidationErrors([
            'first_name',
            'last_name',
            'user_note',
        ]);

        $response = $this->makeReservationStoreRequest($voucher, $product);
        $response->assertSuccessful();
        $response->assertJsonStructure(['data' => $this->productReservationResourceStructure]);

        $reservation = ProductReservation::find($response->json('data.id'));
        $this->assertNotNull($reservation);

        return $reservation;
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
                'fund_id' => $fund->id,
                'state' => FundProvider::STATE_ACCEPTED,
                'allow_budget' => true,
                'allow_products' => true,
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
}
