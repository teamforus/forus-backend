<?php

namespace Tests\Feature;

use App\Mail\ProductReservations\ProductReservationAcceptedMail;
use App\Models\Fund;
use App\Models\Organization;
use App\Models\Product;
use App\Models\ProductReservation;
use App\Models\Voucher;
use App\Services\MailDatabaseLoggerService\Models\EmailLog;
use App\Services\MailDatabaseLoggerService\Traits\AssertsSentEmails;
use Carbon\Carbon;
use Exception;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Tests\Traits\MakesProductReservations;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestVouchers;
use Tests\Traits\TestsReservations;
use Throwable;

class ProductReservationTest extends TestCase
{
    use MakesTestFunds;
    use MakesTestVouchers;
    use TestsReservations;
    use AssertsSentEmails;
    use DatabaseTransactions;
    use MakesProductReservations;

    /**
     * @throws Exception
     * @return void
     */
    public function testReservationWithBudgetVoucher(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());

        $this->assertNotNull($organization);
        $this->makeProviderAndProducts($this->makeTestFund($organization), 1);

        $voucher = $this->findVoucherForReservation($organization, Fund::TYPE_BUDGET);
        $product = $this->findProductForReservation($voucher);

        $this->checkValidReservation($voucher, $product);
    }

    /**
     * @throws Exception
     * @return void
     */
    public function testReservationWithSubsidyVoucherAsUser(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());

        $this->assertNotNull($organization);
        $this->makeProviderAndProducts($this->makeTestSubsidyFund($organization));

        $voucher = $this->findVoucherForReservation($organization, Fund::TYPE_SUBSIDIES);
        $product = $this->findProductForReservation($voucher);

        $this->checkValidReservation($voucher, $product);
    }

    /**
     * @throws Exception
     * @return void
     */
    public function testReservationWithBudgetVoucherAsGuest(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());

        $this->assertNotNull($organization);
        $this->makeProviderAndProducts($this->makeTestFund($organization), 1);

        $voucher = $this->findVoucherForReservation($organization, Fund::TYPE_BUDGET);
        $product = $this->findProductForReservation($voucher);

        $this->makeReservationStoreRequest($voucher, $product, [], false)->assertUnauthorized();
    }

    /**
     * @throws Exception
     * @return void
     */
    public function testReservationWithSubsidyVoucherAsGuest(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());

        $this->assertNotNull($organization);
        $this->makeProviderAndProducts($this->makeTestSubsidyFund($organization));

        $voucher = $this->findVoucherForReservation($organization, Fund::TYPE_SUBSIDIES);
        $product = $this->findProductForReservation($voucher);

        $this->makeReservationStoreRequest($voucher, $product, [], false)->assertUnauthorized();
    }

    /**
     * @throws Exception
     * @return void
     */
    public function testReservationWithInvalidVoucher(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());

        $this->assertNotNull($organization);
        $this->makeProviderAndProducts($this->makeTestFund($organization), 1);
        $this->makeProviderAndProducts($this->makeTestSubsidyFund($organization));

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
     * @throws Exception
     * @return void
     */
    public function testReservationWithInvalidProduct(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());

        $this->assertNotNull($organization);
        $this->makeProviderAndProducts($this->makeTestFund($organization), 1);
        $this->makeProviderAndProducts($this->makeTestSubsidyFund($organization));

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
     * @throws Exception
     * @return void
     */
    public function testReservationProviderWithBudgetVoucher(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());

        $this->assertNotNull($organization);
        $this->makeProviderAndProducts($this->makeTestFund($organization), 1);

        $voucher = $this->findVoucherForReservation($organization, Fund::TYPE_BUDGET);
        $product = $this->findProductForReservation($voucher);

        $this->checkAcceptAndRejectByProvider($voucher, $product);
    }

    /**
     * @throws Exception
     * @return void
     */
    public function testReservationProviderWithSubsidyVoucher(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());

        $this->assertNotNull($organization);
        $this->makeProviderAndProducts($this->makeTestSubsidyFund($organization));

        $voucher = $this->findVoucherForReservation($organization, Fund::TYPE_SUBSIDIES);
        $product = $this->findProductForReservation($voucher);

        $this->checkAcceptAndRejectByProvider($voucher, $product);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testReservationArchiving(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());

        $this->assertNotNull($organization);
        $this->makeProviderAndProducts($this->makeTestFund($organization), 1);

        $voucher = $this->findVoucherForReservation($organization, Fund::TYPE_BUDGET);
        $product = $this->findProductForReservation($voucher);

        $this->checkReservationArchiving($voucher, $product);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testReservationExpireOffset(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());

        $this->assertNotNull($organization);
        $this->makeProviderAndProducts($this->makeTestFund($organization), 1);

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
     * @throws Throwable
     * @return void
     */
    public function testReservationNote(): void
    {
        $startTime = now();
        $organization = $this->makeTestOrganization($this->makeIdentity($this->makeUniqueEmail()));

        $this->makeProviderAndProducts($this->makeTestFund($organization), 1);

        $voucher = $this->findVoucherForReservation($organization, Fund::TYPE_BUDGET);
        $product = $this->findProductForReservation($voucher);

        $reservation = $this->makeReservation($voucher, $product);
        $this->makeReservationGetRequest($reservation)->assertSuccessful();

        // set global reservation note
        $product->organization->update([
            'reservation_note' => true,
            'reservation_note_text' => 'global-note',
        ]);

        // assert global note
        DB::beginTransaction();
        $reservation->acceptProvider();
        $this->assertStringContainsString('global-note', $this->getEmailLog($voucher, $startTime)->content);
        DB::rollBack();

        // assert custom note
        DB::beginTransaction();
        $product->update([
            'reservation_note' => Product::RESERVATION_FIELD_CUSTOM,
            'reservation_note_text' => 'custom-local-note',
        ]);

        $reservation->acceptProvider();
        $this->assertStringContainsString('custom-local-note', $this->getEmailLog($voucher, $startTime)->content);
        $this->assertStringNotContainsString('global-note', $this->getEmailLog($voucher, $startTime)->content);
        DB::rollBack();

        // assert product note settings as 'no'
        DB::beginTransaction();
        $product->update([
            'reservation_note' => Product::RESERVATION_FIELD_NO,
        ]);

        $reservation->acceptProvider();
        $this->assertStringNotContainsString('global-note', $this->getEmailLog($voucher, $startTime)->content);
        DB::rollBack();
    }

    /**
     * @param Voucher $voucher
     * @param Carbon $startTime
     * @return EmailLog
     */
    protected function getEmailLog(Voucher $voucher, Carbon $startTime): EmailLog
    {
        return $this->findEmailLog($voucher->identity, ProductReservationAcceptedMail::class, $startTime);
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

        $response = $this->post(
            "/api/v1/platform/organizations/$providers->id/product-reservations/$reservation->id/archive",
            [],
            $headers,
        );

        if ($assertSuccess) {
            $response->assertSuccessful();
            $response->assertJsonStructure(['data' => $this->productReservationResourceStructure]);
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

        $response = $this->post(
            "/api/v1/platform/organizations/$providers->id/product-reservations/$reservation->id/unarchive",
            [],
            $apiHeaders,
        );

        if ($assertSuccess) {
            $response->assertSuccessful();
            $response->assertJsonStructure(['data' => $this->productReservationResourceStructure]);
        } else {
            $response->assertForbidden();
        }

        if ($assertSuccess) {
            $this->assertTrue(!$reservation->refresh()->isArchived());
        }
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

        // accept reservation
        $response = $this->post(
            "/api/v1/platform/organizations/$provider->id/product-reservations/$reservation->id/accept",
            [],
            $this->makeApiHeaders($this->makeIdentityProxy($provider->employees[0]->identity)),
        );

        if ($assertSuccess) {
            $response->assertSuccessful();
            $response->assertJsonFragment([
                'state' => ProductReservation::STATE_ACCEPTED,
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
        $response->assertJsonStructure(['data' => $this->productReservationResourceStructure]);

        $this->makeReservationCancelRequest($reservation)->assertSuccessful();

        $reservation = ProductReservation::find($reservation->id);
        $this->assertTrue($reservation->isCanceledByClient());
    }

    /**
     * @param Voucher $voucher
     * @param Product $product
     * @throws Throwable
     * @return void
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
        $this->post(
            "/api/v1/platform/organizations/$product->organization_id/product-reservations/$reservation->id/reject",
            [],
            $headers
        )->assertJsonFragment([
            'state' => ProductReservation::STATE_CANCELED_BY_PROVIDER,
        ]);

        $this->assertSame((float) $voucher->amount_available, $originalAmount);

        $reservation = ProductReservation::find($reservation->id);
        $this->assertTrue($reservation->isCanceledByProvider());
    }
}
