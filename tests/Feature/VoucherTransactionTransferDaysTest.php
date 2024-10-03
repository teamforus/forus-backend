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

class VoucherTransactionTransferDaysTest extends TestCase
{
    use MakesTestFunds;
    use DatabaseTransactions;
    use MakesTestOrganizations;
    use MakesProductReservations;
    use MakesTestFundProviders;

    /**
     * @return void
     * @throws \Throwable
     */
    public function testTransactionTransferDays(): void
    {
        $sponsorOrganization = $this->makeTestOrganization($this->makeIdentity($this->makeUniqueEmail()));
        $providerOrganization = $this->makeTestOrganization($this->makeIdentity());

        $fund = $this->makeTestFund($sponsorOrganization);
        $this->makeTestFundProvider($providerOrganization, $fund);

        $voucher = $this->findVoucherForReservation($sponsorOrganization, $fund->type);
        $product = $this->findProductForReservation($voucher);

        $this->checkTransactionTransferDays($voucher, $product);
    }

    /**
     * @param Voucher $voucher
     * @param Product $product
     * @return void
     * @throws \Throwable
     */
    private function checkTransactionTransferDays(Voucher $voucher, Product $product): void
    {
        $reservation = $this->makeReservation($voucher, $product);
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

    /**
     * @param Voucher $voucher
     * @param Product $product
     * @return ProductReservation
     */
    private function makeReservation(Voucher $voucher, Product $product): ProductReservation
    {
        $identity = $voucher->identity;
        $proxy = $this->makeIdentityProxy($identity);
        $headers = $this->makeApiHeaders($proxy);

        $response = $this->post("/api/v1/platform/product-reservations", [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'user_note' => '',
            'voucher_address' => $voucher->token_without_confirmation->address,
            'product_id' => $product->id
        ], $headers);

        /** @var ProductReservation $reservation */
        $reservation = ProductReservation::find($response->json('data.id'));
        $this->assertNotNull($reservation);

        return $reservation;
    }
}
