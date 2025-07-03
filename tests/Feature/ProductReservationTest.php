<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductReservation;
use App\Models\Voucher;
use App\Services\MailDatabaseLoggerService\Traits\AssertsSentEmails;
use Exception;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
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
        $fund = $this->makeTestFund($organization);

        $this->makeProviderAndProducts($fund, 1);

        $voucher = $this->makeTestVoucher($fund, identity: $this->makeIdentity());
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
        $fund = $this->makeTestFund($organization);

        $this->makeProviderAndProducts($fund, 1);

        $voucher = $this->makeTestVoucher($fund, identity: $this->makeIdentity());
        $product = $this->findProductForReservation($voucher);

        $this->makeReservationStoreRequest($voucher, $product, [], false)->assertUnauthorized();
    }

    /**
     * @throws Exception
     * @return void
     */
    public function testReservationProviderWithBudgetVoucher(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization);

        $this->makeProviderAndProducts($fund, 1);

        $voucher = $this->makeTestVoucher($fund, identity: $this->makeIdentity());
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
        $fund = $this->makeTestFund($organization);

        $this->makeProviderAndProducts($fund, 1);

        $voucher = $this->makeTestVoucher($fund, identity: $this->makeIdentity());
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
        $fund = $this->makeTestFund($organization);

        $this->makeProviderAndProducts($fund, 1);

        $voucher = $this->makeTestVoucher($fund, identity: $this->makeIdentity());
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
