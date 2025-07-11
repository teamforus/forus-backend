<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductReservation;
use App\Models\Voucher;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tests\Traits\MakesProductReservations;
use Tests\Traits\MakesTestFundProviders;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestOrganizations;
use Tests\Traits\TestsReservations;
use Throwable;

class VoucherTransactionTransferDaysTest extends TestCase
{
    use MakesTestFunds;
    use TestsReservations;
    use DatabaseTransactions;
    use MakesTestOrganizations;
    use MakesProductReservations;
    use MakesTestFundProviders;

    /**
     * @throws Throwable
     * @return void
     */
    public function testTransactionTransferDays(): void
    {
        $sponsorOrganization = $this->makeTestOrganization($this->makeIdentity($this->makeUniqueEmail()));
        $providerOrganization = $this->makeTestOrganization($this->makeIdentity());

        $fund = $this->makeTestFund($sponsorOrganization);
        $this->makeTestFundProvider($providerOrganization, $fund);

        $voucher = $this->makeTestVoucher($fund, identity: $this->makeIdentity());
        $product = $this->findProductForReservation($voucher);

        $this->checkTransactionTransferDays($voucher, $product);
    }

    /**
     * @param Voucher $voucher
     * @param Product $product
     * @throws Throwable
     * @return void
     */
    private function checkTransactionTransferDays(Voucher $voucher, Product $product): void
    {
        $response = $this->makeReservationStoreRequest($voucher, $product);
        $reservation = ProductReservation::find($response->json('data.id'));

        $this->assertNotNull($reservation);
        $reservation->acceptProvider();

        $transaction = $reservation->voucher_transaction;
        $this->assertNotNull($transaction);

        $organization = $voucher->fund->organization;
        $voucherEndpoint = "/api/v1/platform/organizations/$organization->id/sponsor/transactions/$transaction->address";
        $sponsorAuthHeaders = $this->makeApiHeaders($this->makeIdentityProxy($organization->identity));

        $response = $this->getJson($voucherEndpoint, $sponsorAuthHeaders);
        $response->assertSuccessful();

        $transactionIn = $response->json('data.transfer_in');
        $this->assertGreaterThan(0, $transactionIn);

        Carbon::setTestNow(now()->addDays($transactionIn + 5));

        $response = $this->getJson($voucherEndpoint, $sponsorAuthHeaders);
        $response->assertSuccessful();

        $transactionIn = $response->json('data.transfer_in');
        $this->assertEquals(0, $transactionIn);
    }
}
