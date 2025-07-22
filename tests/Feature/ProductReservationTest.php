<?php

namespace Tests\Feature;

use App\Mail\ProductReservations\ProductReservationCanceledMail;
use App\Mail\ProductReservations\ProductReservationRejectedMail;
use App\Models\Product;
use App\Models\ProductReservation;
use App\Models\Voucher;
use App\Services\MailDatabaseLoggerService\Traits\AssertsSentEmails;
use Carbon\Carbon;
use Exception;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Tests\Traits\MakesApiRequests;
use Tests\Traits\MakesProductReservations;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestVouchers;
use Tests\Traits\TestsReservations;
use Throwable;

class ProductReservationTest extends TestCase
{
    use MakesTestFunds;
    use MakesApiRequests;
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
     * @throws Throwable
     * @return void
     */
    public function testProductReservationCanceledMailLog(): void
    {
        $startTime = now();
        $identity = $this->makeIdentity($this->makeUniqueEmail());
        $organization = $this->makeTestOrganization($identity);
        $voucher = $this->makeTestVoucher($this->makeTestFund($organization), $identity);
        $product = $this->findProductForReservation($voucher);
        $reservation = $this->makeReservation($voucher, $product);
        $note = 'Test reservation note from provider';

        // assert reject reservation and provider note exists in mail if notify parameter is true
        DB::beginTransaction();
        $this->assertRejectedReservationProviderNote($reservation, $note, $startTime, true);
        DB::rollBack();

        // assert reject reservation and provider note doesn't exist in mail if notify parameter is false
        DB::beginTransaction();
        $this->assertRejectedReservationProviderNote($reservation, $note, $startTime, false);
        DB::rollBack();

        // accept reservation and assert cancel reservation
        $reservation->acceptProvider();

        // assert cancel reservation and provider note exists in mail if notify parameter is true
        DB::beginTransaction();
        $this->assertCanceledReservationProviderNote($reservation, $note, $startTime, true);
        DB::rollBack();

        // assert cancel reservation and provider note doesn't exist in mail if notify parameter is false
        DB::beginTransaction();
        $this->assertCanceledReservationProviderNote($reservation, $note, $startTime, false);
        DB::rollBack();
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
     * @param ProductReservation $reservation
     * @param string $note
     * @param Carbon $after
     * @param bool $assertExists
     * @return void
     */
    protected function assertRejectedReservationProviderNote(
        ProductReservation $reservation,
        string $note,
        Carbon $after,
        bool $assertExists
    ): void {
        $providerIdentity = $reservation->product->organization->identity;

        $this->apiCancelReservationByProvider($reservation, $providerIdentity, [
            'note' => $note,
            'notify_with_note' => $assertExists,
        ]);

        $this->assertCancelReservationProviderNoteInMail(
            $reservation->voucher->identity->email,
            ProductReservationRejectedMail::class,
            $after,
            $note,
            $assertExists
        );
    }

    /**
     * @param ProductReservation $reservation
     * @param string $note
     * @param Carbon $after
     * @param bool $assertExists
     * @return void
     */
    protected function assertCanceledReservationProviderNote(
        ProductReservation $reservation,
        string $note,
        Carbon $after,
        bool $assertExists
    ): void {
        $providerIdentity = $reservation->product->organization->identity;

        $this->apiCancelReservationByProvider($reservation, $providerIdentity, [
            'note' => $note,
            'notify_with_note' => $assertExists,
        ]);

        $this->assertCancelReservationProviderNoteInMail(
            $reservation->voucher->identity->email,
            ProductReservationCanceledMail::class,
            $after,
            $note,
            $assertExists
        );
    }

    /**
     * @param string $email
     * @param string $mailable
     * @param Carbon $after
     * @param string $note
     * @param bool $assertExists
     * @return void
     */
    protected function assertCancelReservationProviderNoteInMail(
        string $email,
        string $mailable,
        Carbon $after,
        string $note,
        bool $assertExists
    ): void {
        $this->assertMailableSent($email, $mailable, $after);
        $email = $this->getEmailOfTypeQuery($email, $mailable, $after)->first();

        $assertExists
            ? $this->assertStringContainsString($note, $email->content)
            : $this->assertStringNotContainsString($note, $email->content);
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

        $reservation = $this->apiCancelReservationByProvider($reservation, $product->organization->employees->first()->identity);

        $this->assertSame($reservation->state, ProductReservation::STATE_CANCELED_BY_PROVIDER);
        $this->assertSame((float) $voucher->amount_available, $originalAmount);
        $this->assertTrue($reservation->isCanceledByProvider());
    }
}
