<?php

namespace Feature;

use App\Models\Product;
use App\Models\ProductReservation;
use App\Models\Voucher;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tests\Traits\MakesProductReservations;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestOrganizations;

class VoucherTransactionTransferDaysTest extends TestCase
{
    use DatabaseTransactions, MakesTestFunds, MakesTestOrganizations, MakesProductReservations;

    /**
     * @var string
     */
    protected string $apiUrl = '/api/v1/platform/product-reservations';

    /**
     * @var string
     */
    protected string $sponsorApiUrl = '/api/v1/platform/organizations/%s/sponsor/transactions/%s';

    /**
     * @return void
     * @throws \Throwable
     */
    public function testTransactionTransferDays(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity($this->makeUniqueEmail()));

        $fund = $this->makeTestFund($organization);

        $voucher = $this->findVoucherForReservation($organization, $fund->type);
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

        $proxy = $this->makeIdentityProxy($organization->identity);
        $headers = $this->makeApiHeaders($proxy);

        $response = $this->get(sprintf(
            $this->sponsorApiUrl, $organization->id, $transaction->address
        ), $headers);
        $response->assertSuccessful();

        $transactionIn = $response->json('data.transaction_in');
        $this->assertGreaterThan(0, $transactionIn);

        Carbon::setTestNow(now()->addDays($transactionIn + 5));

        $response = $this->get(sprintf(
            $this->sponsorApiUrl, $organization->id, $transaction->address
        ), $headers);
        $response->assertSuccessful();

        $transactionIn = $response->json('data.transaction_in');
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

        $response = $this->post($this->apiUrl, [
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
