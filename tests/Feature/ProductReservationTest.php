<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Fund;
use App\Models\Identity;
use App\Models\Organization;
use App\Models\Product;
use App\Models\ProductReservation;
use App\Models\Voucher;
use App\Services\MailDatabaseLoggerService\Traits\AssertsSentEmails;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tests\Traits\MakesProductReservations;

class ProductReservationTest extends TestCase
{
    use AssertsSentEmails, MakesProductReservations, DatabaseTransactions;

    /**
     * @var string
     */
    protected string $apiUrl = '/api/v1/platform/product-reservations';

    /**
     * @var string
     */
    protected string $apiOrganizationUrl = '/api/v1/platform/organizations/%s/product-reservations';

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
    protected string $organizationSubsidyName = 'Nijmegen';

    /**
     * @var string
     */
    protected string $organizationBudgetName = 'Nijmegen';

    /**
     * @return void
     * @throws \Exception
     */
    public function testReservationWithBudgetVoucher(): void
    {
        /** @var Organization $organization */
        $organization = Organization::where('name', $this->organizationBudgetName)->first();
        $this->assertNotNull($organization);

        $identity = $organization->identity;
        $voucher = $this->findVoucherForReservation($organization, Fund::TYPE_BUDGET);
        $product = $this->findProductForReservation($voucher);

        $this->checkValidReservation($identity, $voucher, $product);
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function testReservationWithSubsidyVoucher(): void
    {
        /** @var Organization $organization */
        $organization = Organization::where('name', $this->organizationSubsidyName)->first();
        $this->assertNotNull($organization);

        $identity = $organization->identity;
        $voucher = $this->findVoucherForReservation($organization, Fund::TYPE_SUBSIDIES);
        $product = $this->findProductForReservation($voucher);

        $this->checkValidReservation($identity, $voucher, $product);
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function testReservationWithBudgetVoucherAsGuest(): void
    {
        /** @var Organization $organization */
        $organization = Organization::where('name', $this->organizationBudgetName)->first();
        $this->assertNotNull($organization);

        $voucher = $this->findVoucherForReservation($organization, Fund::TYPE_BUDGET);
        $product = $this->findProductForReservation($voucher);

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
        $organization = Organization::where('name', $this->organizationSubsidyName)->first();
        $this->assertNotNull($organization);

        $voucher = $this->findVoucherForReservation($organization, Fund::TYPE_SUBSIDIES);
        $product = $this->findProductForReservation($voucher);

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
        $organization = Organization::where('name', $this->organizationSubsidyName)->first();
        $this->assertNotNull($organization);

        $identity = $organization->identity;

        Organization::where('reservations_subsidy_enabled', true)
            ->update(['reservations_subsidy_enabled' => false]);

        $voucher = $this->findVoucherForReservation($organization, Fund::TYPE_SUBSIDIES);
        $voucherBudget = $this->findVoucherForReservation($organization, Fund::TYPE_BUDGET);
        $product = $this->findProductForReservation($voucherBudget);

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
        $organization = Organization::where('name', $this->organizationSubsidyName)->first();
        $this->assertNotNull($organization);

        $identity = $organization->identity;

        Organization::where('reservations_budget_enabled', true)
            ->update(['reservations_budget_enabled' => false]);

        $voucherSubsidy = $this->findVoucherForReservation($organization, Fund::TYPE_SUBSIDIES);
        $voucher = $this->findVoucherForReservation($organization, Fund::TYPE_BUDGET);
        $product = $this->findProductForReservation($voucherSubsidy);

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
     * @return void
     * @throws \Exception
     */
    public function testReservationProviderWithBudgetVoucher(): void
    {
        /** @var Organization $organization */
        $organization = Organization::where('name', $this->organizationSubsidyName)->first();
        $this->assertNotNull($organization);

        $identity = $organization->identity;
        $voucher = $this->findVoucherForReservation($organization, Fund::TYPE_BUDGET);
        $product = $this->findProductForReservation($voucher);

        $this->checkAcceptAndRejectByProvider($identity, $voucher, $product);
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function testReservationProviderWithSubsidyVoucher(): void
    {
        /** @var Organization $organization */
        $organization = Organization::where('name', $this->organizationSubsidyName)->first();
        $this->assertNotNull($organization);

        $identity = $organization->identity;
        $voucher = $this->findVoucherForReservation($organization, Fund::TYPE_SUBSIDIES);
        $product = $this->findProductForReservation($voucher);

        $this->checkAcceptAndRejectByProvider($identity, $voucher, $product);
    }

    /**
     * @return void
     * @throws \Throwable
     */
    public function testReservationArchiving(): void
    {
        /** @var Organization $organization */
        $organization = Organization::where('name', $this->organizationBudgetName)->first();
        $this->assertNotNull($organization);

        $identity = $organization->identity;
        $voucher = $this->findVoucherForReservation($organization, Fund::TYPE_BUDGET);
        $product = $this->findProductForReservation($voucher);

        $this->checkReservationArchiving($identity, $voucher, $product);
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
    ): void {
        $proxy = $this->makeIdentityProxy($identity);
        $headers = $this->makeApiHeaders($proxy);

        $reservation = $this->makeReservation($identity, $voucher, $product);
        $reservationUrl = "$this->apiUrl/$reservation->id";

        // check show method
        $response = $this->getJson($reservationUrl, $headers);

        $response->assertSuccessful();
        $response->assertJsonStructure(['data' => $this->resourceStructure]);

        // cancel reservation
        $this->patch($reservationUrl, [
            'state' => ProductReservation::STATE_CANCELED_BY_CLIENT,
        ], $headers)->assertSuccessful();

        $reservation = ProductReservation::find($reservation->id);
        $this->assertTrue($reservation->isCanceledByClient());
    }

    /**
     * @param Identity $identity
     * @param Voucher $voucher
     * @param Product $product
     * @return void
     * @throws \Throwable
     */
    private function checkReservationArchiving(
        Identity $identity,
        Voucher $voucher,
        Product $product
    ): void {
        $proxy = $this->makeIdentityProxy($identity);
        $headers = $this->makeApiHeaders($proxy);
        $reservation = $this->makeReservation($identity, $voucher, $product);

        $this->archiveReservation($reservation, $headers, false);

        $reservation->acceptProvider();
        /*$this->archiveReservation($reservation, $headers);
        $this->unarchiveReservation($reservation, $headers);*/

        $reservation->rejectOrCancelProvider();
        /*$this->archiveReservation($reservation, $headers);
        $this->unarchiveReservation($reservation, $headers);*/
    }

    /**
     * @param ProductReservation $reservation
     * @param array $apiHeaders
     * @param bool $assertSuccess
     * @return void
     */
    protected function archiveReservation(
        ProductReservation $reservation,
        array $apiHeaders,
        bool $assertSuccess = true
    ): void {
        $providers = $reservation->product->organization;
        $reservationUrl = sprintf($this->apiOrganizationUrl . '/%s', $providers->id, $reservation->id);
        $response = $this->post("$reservationUrl/archive", [], $apiHeaders);

        if ($assertSuccess) {
            $response->assertSuccessful();
            $response->assertJsonStructure(['data' => $this->resourceStructure]);
        } else {
            $response->assertForbidden();
        }

        if ($assertSuccess) {
            $this->assertTrue($reservation->refresh()->isArchived());
        }
    }

    /**
     * @param ProductReservation $reservation
     * @param array $apiHeaders
     * @param bool $assertSuccess
     * @return void
     */
    protected function unarchiveReservation(
        ProductReservation $reservation,
        array $apiHeaders,
        bool $assertSuccess = true
    ): void {
        $providers = $reservation->product->organization;
        $reservationUrl = sprintf($this->apiOrganizationUrl . '/%s', $providers->id, $reservation->id);
        $response = $this->post("$reservationUrl/unarchive", [], $apiHeaders);

        if ($assertSuccess) {
            $response->assertSuccessful();
            $response->assertJsonStructure(['data' => $this->resourceStructure]);
        } else {
            $response->assertForbidden();
        }

        if ($assertSuccess) {
            $this->assertTrue(!$reservation->refresh()->isArchived());
        }
    }

    /**
     * @param Identity $identity
     * @param Voucher $voucher
     * @param Product $product
     * @return ProductReservation
     */
    public function makeReservation(
        Identity $identity,
        Voucher $voucher,
        Product $product
    ): ProductReservation {
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

        /** @var ProductReservation $reservation */
        $reservation = ProductReservation::find($response->json('data.id'));
        $this->assertNotNull($reservation);

        return $reservation;
    }

    /**
     * @param Identity $identity
     * @param Voucher $voucher
     * @param Product $product
     * @return void
     */
    private function checkAcceptAndRejectByProvider(
        Identity $identity,
        Voucher $voucher,
        Product $product
    ): void {
        $originalAmount = floatval($voucher->amount_available);
        $amount = floatval($product->price);
        $startTime = now();

        $reservation = $this->makeReservation($identity, $voucher, $product);
        $autoAccept = $product->organization->reservations_auto_accept;
        $stateIsValid = $autoAccept ? $reservation->isAccepted() : $reservation->isPending();

        $this->assertTrue($stateIsValid, 'Wrong reservation status');
        $this->assertTrue(floatval($voucher->amount_available) === $originalAmount - $amount);

        $providerOrganization = $product->organization;
        $this->assertNotNull($providerOrganization);

        /** @var Employee $employee */
        $employee = $providerOrganization->employees->first();
        $this->assertNotNull($employee);

        $proxy = $this->makeIdentityProxy($employee->identity);
        $headers = $this->makeApiHeaders($proxy);

        if (!$autoAccept) {
            // accept reservation
            $acceptUrl = sprintf(
                $this->apiOrganizationUrl . '/%s/accept', $providerOrganization->id, $reservation->id
            );
            $this->post($acceptUrl, [], $headers)->assertJsonFragment([
                'state' => ProductReservation::STATE_ACCEPTED
            ]);

            $this->assertTrue(floatval($voucher->amount_available) === $originalAmount - $amount);

            $reservation = ProductReservation::find($reservation->id);
            $this->assertTrue($reservation->isAccepted());

            // check transaction exists
            $transaction = $reservation->product_voucher->transactions()
                ->where('created_at', '>=', $startTime)
                ->first();

            $this->assertNotNull($transaction);
        }

        // reject reservation
        $rejectUrl = sprintf(
            $this->apiOrganizationUrl . '/%s/reject', $providerOrganization->id, $reservation->id
        );
        $this->post($rejectUrl, [], $headers)->assertJsonFragment([
            'state' => ProductReservation::STATE_CANCELED_BY_PROVIDER
        ]);

        $this->assertTrue(floatval($voucher->amount_available) === $originalAmount);

        $reservation = ProductReservation::find($reservation->id);
        $this->assertTrue($reservation->isCanceledByProvider());
    }
}
