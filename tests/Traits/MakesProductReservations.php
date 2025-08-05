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
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\WithFaker;
use Throwable;

trait MakesProductReservations
{
    use WithFaker;
    use HasDbTokens;
    use MakesTestVouchers;
    use MakesTestProducts;
    use TestsReservations;

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
     * @param Voucher $voucher
     * @throws Exception
     * @return Product
     */
    public function findProductForReservation(Voucher $voucher): Product
    {
        $product = ProductQuery::whereAvailableForVoucher(Product::query(), $voucher)->first();

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
        $voucher = $this->makeTestVoucher($organization->funds[0]);
        $product = $this->findProductForReservation($voucher);

        $reservation = $voucher->reserveProduct(product: $product, extraData: [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'user_note' => '',
        ]);

        if ($reservation->product->autoAcceptsReservations()) {
            $reservation->acceptProvider();
        }

        return $reservation;
    }

    /**
     * @param Voucher $voucher
     * @param Product $product
     * @param array $fields
     * @return ProductReservation
     */
    public function makeReservation(Voucher $voucher, Product $product, array $fields = []): ProductReservation
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

        $response = $this->makeReservationStoreRequest($voucher, $product, $fields);
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
    protected function createProductForReservation(Organization $organization, Collection|array $funds): Product
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
            collect($funds)->pluck('id')->toArray(),
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
