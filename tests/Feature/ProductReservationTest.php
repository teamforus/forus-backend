<?php

namespace Tests\Feature;

use App\Models\Fund;
use App\Models\Organization;
use App\Models\Product;
use App\Models\ProductReservation;
use App\Models\Voucher;
use App\Services\MailDatabaseLoggerService\Traits\AssertsSentEmails;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;
use Tests\Traits\MakesProductReservations;
use Tests\Traits\MakesTestVouchers;
use Tests\Traits\TestsReservations;

class ProductReservationTest extends TestCase
{
    use MakesTestVouchers;
    use TestsReservations;
    use AssertsSentEmails;
    use DatabaseTransactions;
    use MakesProductReservations;

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

        $voucher = $this->findVoucherForReservation($organization, Fund::TYPE_BUDGET);
        $product = $this->findProductForReservation($voucher);

        $this->checkValidReservation($voucher, $product);
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function testReservationWithSubsidyVoucher(): void
    {
        $organization = Organization::firstWhere('name', $this->organizationSubsidyName);
        $this->assertNotNull($organization);

        $voucher = $this->findVoucherForReservation($organization, Fund::TYPE_SUBSIDIES);
        $product = $this->findProductForReservation($voucher);

        $this->checkValidReservation($voucher, $product);
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

        $this->makeReservationStoreRequest($voucher, $product, [], false)->assertUnauthorized();
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function testReservationWithSubsidyVoucherAsGuest(): void
    {
        $organization = Organization::firstWhere('name', $this->organizationBudgetName);
        $this->assertNotNull($organization);

        $voucher = $this->findVoucherForReservation($organization, Fund::TYPE_SUBSIDIES);
        $product = $this->findProductForReservation($voucher);

        $this->makeReservationStoreRequest($voucher, $product, [], false)->assertUnauthorized();
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function testReservationWithInvalidVoucher(): void
    {
        $organization = Organization::firstWhere('name', $this->organizationBudgetName);
        $this->assertNotNull($organization);

        Organization::query()
            ->where('reservations_subsidy_enabled', true)
            ->update(['reservations_subsidy_enabled' => false]);

        $voucher = $this->findVoucherForReservation($organization, Fund::TYPE_SUBSIDIES);
        $voucherBudget = $this->findVoucherForReservation($organization, Fund::TYPE_BUDGET);
        $product = $this->findProductForReservation($voucherBudget);

        $this
            ->makeReservationStoreRequest($voucher, $product)
            ->assertJsonValidationErrorFor('product_id');

        Organization::query()
            ->where('reservations_subsidy_enabled', false)
            ->update(['reservations_subsidy_enabled' => true]);
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function testReservationWithInvalidProduct(): void
    {
        $organization = Organization::firstWhere('name', $this->organizationBudgetName);
        $this->assertNotNull($organization);

        Organization::query()
            ->where('reservations_budget_enabled', true)
            ->update(['reservations_budget_enabled' => false]);

        $voucherSubsidy = $this->findVoucherForReservation($organization, Fund::TYPE_SUBSIDIES);
        $voucher = $this->findVoucherForReservation($organization, Fund::TYPE_BUDGET);
        $product = $this->findProductForReservation($voucherSubsidy);

        $this
            ->makeReservationStoreRequest($voucher, $product)
            ->assertJsonValidationErrorFor('product_id');

        Organization::where('reservations_budget_enabled', false)
            ->update(['reservations_budget_enabled' => true]);
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function testReservationProviderWithBudgetVoucher(): void
    {
        $organization = Organization::firstWhere('name', $this->organizationBudgetName);
        $this->assertNotNull($organization);

        $voucher = $this->findVoucherForReservation($organization, Fund::TYPE_BUDGET);
        $product = $this->findProductForReservation($voucher);

        $this->checkAcceptAndRejectByProvider($voucher, $product);
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function testReservationProviderWithSubsidyVoucher(): void
    {
        $organization = Organization::firstWhere('name', $this->organizationBudgetName);
        $this->assertNotNull($organization);

        $voucher = $this->findVoucherForReservation($organization, Fund::TYPE_SUBSIDIES);
        $product = $this->findProductForReservation($voucher);

        $this->checkAcceptAndRejectByProvider($voucher, $product);
    }

    /**
     * @return void
     * @throws \Throwable
     */
    public function testReservationArchiving(): void
    {
        $organization = Organization::firstWhere('name', $this->organizationBudgetName);
        $this->assertNotNull($organization);

        $voucher = $this->findVoucherForReservation($organization, Fund::TYPE_BUDGET);
        $product = $this->findProductForReservation($voucher);

        $this->checkReservationArchiving($voucher, $product);
    }

    /**
     * @return void
     * @throws \Throwable
     */
    public function testReservationExpireOffset(): void
    {
        $organization = Organization::firstWhere('name', $this->organizationBudgetName);
        $this->assertNotNull($organization);

        $voucher = $this->findVoucherForReservation($organization, Fund::TYPE_BUDGET);
        $product = $this->findProductForReservation($voucher);

        $originalAmount = (float) $voucher->amount_available;
        $reservation = $this->makeReservation($voucher, $product);
        $this->expireVoucherAndFund($voucher, now()->subDay());

        $voucher->fund->fund_config->update([
            'reservation_approve_offset' => 0,
        ]);

        $this->acceptReservation($originalAmount, $reservation, false);
        Cache::clear();

        $voucher->fund->fund_config->update([
            'reservation_approve_offset' => 1,
        ]);

        $this->acceptReservation($originalAmount, $reservation, true);
    }

    /**
     * @param Voucher $voucher
     * @param Product $product
     * @return void
     */
    private function checkValidReservation(Voucher $voucher, Product $product): void
    {
        $reservation = $this->makeReservation($voucher, $product);
        $response = $this->makeReservationGetRequest($reservation);

        $response->assertSuccessful();
        $response->assertJsonStructure(['data' => $this->resourceStructure]);

        $this->makeReservationCancelRequest($reservation)->assertSuccessful();

        $reservation = ProductReservation::find($reservation->id);
        $this->assertTrue($reservation->isCanceledByClient());
    }

    /**
     * @param Voucher $voucher
     * @param Product $product
     * @return void
     * @throws \Throwable
     */
    private function checkReservationArchiving(
        Voucher $voucher,
        Product $product,
    ): void {
        $reservation = $this->makeReservation($voucher, $product);
        $this->archiveReservationAsProvider($reservation, false);

        $reservation->acceptProvider();
        /*$this->archiveReservation($reservation, $headers);
        $this->unarchiveReservation($reservation, $headers);*/

        $reservation->rejectOrCancelProvider();
        /*$this->archiveReservation($reservation, $headers);
        $this->unarchiveReservation($reservation, $headers);*/
    }

    /**
     * @param ProductReservation $reservation
     * @param bool $assertSuccess
     * @return void
     */
    protected function archiveReservationAsProvider(
        ProductReservation $reservation,
        bool $assertSuccess = true,
    ): void {
        $proxy = $this->makeIdentityProxy($reservation->product->organization->identity);
        $headers = $this->makeApiHeaders($proxy);

        $providers = $reservation->product->organization;
        $reservationUrl = sprintf($this->apiOrganizationUrl . '/%s', $providers->id, $reservation->id);
        $response = $this->post("$reservationUrl/archive", [], $headers);

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
        $response->assertJsonStructure(['data' => $this->resourceStructure]);

        $reservation = ProductReservation::find($response->json('data.id'));
        $this->assertNotNull($reservation);

        return $reservation;
    }

    /**
     * @param Voucher $voucher
     * @param Product $product
     * @return void
     */
    private function checkAcceptAndRejectByProvider(Voucher $voucher, Product $product): void
    {
        $originalAmount = (float) $voucher->amount_available;

        $reservation = $this->makeReservation($voucher, $product);
        $autoAccept = $product->organization->reservations_auto_accept;
        $stateIsValid = $autoAccept ? $reservation->isAccepted() : $reservation->isPending();

        $this->assertTrue($stateIsValid, 'Wrong reservation status');
        $this->assertSame((float) $voucher->amount_available, $originalAmount - $reservation->amount);

        if (!$autoAccept) {
            $this->acceptReservation($originalAmount, $reservation, true);
        }

        $headers = $this->makeApiHeaders($this->makeIdentityProxy(
            $product->organization->employees->first()->identity,
        ));

        // reject reservation
        $rejectUrl = sprintf(
            $this->apiOrganizationUrl . '/%s/reject', $product->organization->id, $reservation->id
        );
        $this->post($rejectUrl, [], $headers)->assertJsonFragment([
            'state' => ProductReservation::STATE_CANCELED_BY_PROVIDER
        ]);

        $this->assertSame((float) $voucher->amount_available, $originalAmount);

        $reservation = ProductReservation::find($reservation->id);
        $this->assertTrue($reservation->isCanceledByProvider());
    }

    /**
     * @param float $originalAmount
     * @param ProductReservation $reservation
     * @param bool $assertSuccess
     * @return void
     */
    protected function acceptReservation(
        float $originalAmount,
        ProductReservation $reservation,
        bool $assertSuccess,
    ): void {
        $startTime = now();

        $provider = $reservation->product->organization;
        $headers = $this->makeApiHeaders($this->makeIdentityProxy(
            $provider->employees->first()->identity,
        ));

        // accept reservation
        $acceptUrl = sprintf($this->apiOrganizationUrl . '/%s/accept', $provider->id, $reservation->id);
        $response = $this->post($acceptUrl, [], $headers);

        if ($assertSuccess) {
            $response->assertSuccessful();
            $response->assertJsonFragment([
                'state' => ProductReservation::STATE_ACCEPTED
            ]);

            $this->assertSame((float) $reservation->voucher->amount_available, $originalAmount - $reservation->amount);

            $reservation = ProductReservation::find($reservation->id);
            $this->assertTrue($reservation->isAccepted());

            // check transaction exists
            $transaction = $reservation->product_voucher->transactions()
                ->where('created_at', '>=', $startTime)
                ->first();

            $this->assertNotNull($transaction);
        } else {
            $response->assertForbidden();
        }
    }
}
