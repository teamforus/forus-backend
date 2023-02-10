<?php

namespace Tests\Feature;

use App\Models\Fund;
use App\Models\Identity;
use App\Models\Organization;
use App\Models\Product;
use App\Models\ProductReservation;
use App\Models\Voucher;
use App\Scopes\Builders\ProductQuery;
use App\Scopes\Builders\ProductSubQuery;
use App\Scopes\Builders\VoucherQuery;
use App\Services\MailDatabaseLoggerService\Traits\AssertsSentEmails;
use Tests\TestCase;

class ProductReservationTest extends TestCase
{
    use AssertsSentEmails;

    /**
     * @var string
     */
    protected string $apiUrl = '/api/v1/platform/product-reservations';

    /**
     * @var array
     */
    protected array $resourceStructure = [
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
     * @var string
     */
    protected string $organizationName = 'Stadjerspas';

    /**
     * @return void
     * @throws \Exception
     */
    public function testReservationWithBudgetVoucher(): void
    {
        /** @var Organization $organization */
        $organization = Organization::where('name', $this->organizationName)->first();
        $this->assertNotNull($organization);

        $identity = $organization->identity;
        $voucher = $this->getVoucherForFundType($organization, Fund::TYPE_BUDGET);
        $product = $this->getProductForFundType($voucher, $identity->address, Fund::TYPE_BUDGET);

        $this->checkValidReservation($identity, $voucher, $product);
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function testReservationWithSubsidyVoucher(): void
    {
        /** @var Organization $organization */
        $organization = Organization::where('name', $this->organizationName)->first();
        $this->assertNotNull($organization);

        $identity = $organization->identity;
        $voucher = $this->getVoucherForFundType($organization, Fund::TYPE_SUBSIDIES);
        $product = $this->getProductForFundType($voucher, $identity->address, Fund::TYPE_SUBSIDIES);

        $this->checkValidReservation($identity, $voucher, $product);
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function testReservationWithBudgetVoucherAsGuest(): void
    {
        /** @var Organization $organization */
        $organization = Organization::where('name', $this->organizationName)->first();
        $this->assertNotNull($organization);

        $identity = $organization->identity;
        $voucher = $this->getVoucherForFundType($organization, Fund::TYPE_BUDGET);
        $product = $this->getProductForFundType($voucher, $identity->address, Fund::TYPE_BUDGET);

        $this->post($this->apiUrl, [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'user_note' => '',
            'voucher_address' => $voucher->token_without_confirmation->address,
            'product_id' => $product->id
        ])->assertUnauthorized();
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function testReservationWithSubsidyVoucherAsGuest(): void
    {
        /** @var Organization $organization */
        $organization = Organization::where('name', $this->organizationName)->first();
        $this->assertNotNull($organization);

        $identity = $organization->identity;
        $voucher = $this->getVoucherForFundType($organization, Fund::TYPE_SUBSIDIES);
        $product = $this->getProductForFundType($voucher, $identity->address, Fund::TYPE_SUBSIDIES);

        $this->post($this->apiUrl, [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'user_note' => '',
            'voucher_address' => $voucher->token_without_confirmation->address,
            'product_id' => $product->id
        ])->assertUnauthorized();
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function testReservationWithInvalidVoucher(): void
    {
        /** @var Organization $organization */
        $organization = Organization::where('name', $this->organizationName)->first();
        $this->assertNotNull($organization);

        $identity = $organization->identity;

        Organization::where('reservations_subsidy_enabled', true)
            ->update(['reservations_subsidy_enabled' => false]);

        $voucher = $this->getVoucherForFundType($organization, Fund::TYPE_SUBSIDIES);
        $voucherBudget = $this->getVoucherForFundType($organization, Fund::TYPE_BUDGET);

        $product = $this->getProductForFundType(
            $voucherBudget, $identity->address, Fund::TYPE_BUDGET
        );

        $proxy = $this->makeIdentityProxy($identity);
        $headers = $this->makeApiHeaders($proxy);

        $this->post($this->apiUrl, [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'user_note' => '',
            'voucher_address' => $voucher->token_without_confirmation->address,
            'product_id' => $product->id
        ], $headers)->assertJsonValidationErrorFor('product_id');

        Organization::where('reservations_subsidy_enabled', false)
            ->update(['reservations_subsidy_enabled' => true]);
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function testReservationWithInvalidProduct(): void
    {
        /** @var Organization $organization */
        $organization = Organization::where('name', $this->organizationName)->first();
        $this->assertNotNull($organization);

        $identity = $organization->identity;

        Organization::where('reservations_budget_enabled', true)
            ->update(['reservations_budget_enabled' => false]);

        $voucherSubsidy = $this->getVoucherForFundType($organization, Fund::TYPE_SUBSIDIES);
        $voucher = $this->getVoucherForFundType($organization, Fund::TYPE_BUDGET);

        $product = $this->getProductForFundType(
            $voucherSubsidy, $identity->address, Fund::TYPE_SUBSIDIES
        );

        $proxy = $this->makeIdentityProxy($identity);
        $headers = $this->makeApiHeaders($proxy);

        $this->post($this->apiUrl, [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'user_note' => '',
            'voucher_address' => $voucher->token_without_confirmation->address,
            'product_id' => $product->id
        ], $headers)->assertJsonValidationErrorFor('product_id');

        Organization::where('reservations_budget_enabled', false)
            ->update(['reservations_budget_enabled' => true]);
    }

    /**
     * @param Organization $organization
     * @param string $fundType
     * @return Voucher
     */
    private function getVoucherForFundType(Organization $organization, string $fundType): Voucher
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
    private function getProductForFundType(
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
     * @param Identity $identity
     * @param Voucher $voucher
     * @param Product $product
     * @return void
     */
    private function checkValidReservation(
        Identity $identity,
        Voucher $voucher,
        Product $product
    ): void
    {
        $proxy = $this->makeIdentityProxy($identity);
        $headers = $this->makeApiHeaders($proxy);

        $this->post($this->apiUrl, [
            'user_note' => [],
            'voucher_address' => $voucher->token_without_confirmation->address,
            'product_id' => $product->id
        ], $headers)->assertJsonValidationErrors([
            'first_name',
            'last_name',
            'user_note',
        ]);

        $response = $this->post($this->apiUrl, [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'user_note' => '',
            'voucher_address' => $voucher->token_without_confirmation->address,
            'product_id' => $product->id
        ], $headers);

        $response->assertJsonStructure(['data' => $this->resourceStructure]);

        $reservation = ProductReservation::find($response->json('data.id'));
        $this->assertNotNull($reservation);

        $reservationUrl = "$this->apiUrl/$reservation->id";

        // check show method
        $response = $this->get($reservationUrl, $headers);

        $response->assertSuccessful();
        $response->assertJsonStructure(['data' => $this->resourceStructure]);

        // cancel reservation
        $this->patch($reservationUrl, [
            'state' => ProductReservation::STATE_CANCELED_BY_CLIENT,
        ], $headers)->assertSuccessful();

        $reservation = ProductReservation::find($reservation->id);
        $this->assertTrue($reservation->isCanceledByClient());
    }
}
