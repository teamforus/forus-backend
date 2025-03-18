<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductReservation;
use App\Models\VoucherTransaction;
use App\Models\VoucherTransactionBulk;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;
use Tests\Traits\MakesProductReservations;
use Tests\Traits\MakesTestBankConnections;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\TestsReservations;
use Throwable;

class VoucherHasPayoutTest extends TestCase
{
    use MakesTestFunds;
    use TestsReservations;
    use DatabaseTransactions;
    use MakesProductReservations;
    use MakesTestBankConnections;

    /**
     * @throws Throwable
     * @return void
     */
    public function testVoucherHasPayoutsWithReservation(): void
    {
        $sponsorOrganization = $this->makeTestOrganization($this->makeIdentity());
        $providerOrganization = $this->makeTestOrganization($this->makeIdentity());

        $fund = $this->makeTestFund($sponsorOrganization);
        $this->makeBankConnection($sponsorOrganization->refresh());
        $this->makeTestFundProvider($providerOrganization, $fund);

        $identity = $this->makeIdentity();
        $voucher = $fund->makeVoucher($identity);
        $products = $this->makeProviderAndProducts($fund);

        /** @var Product $product */
        $product = $products['approved'][0] ?? null;
        $this->assertNotNull($product);

        // by default has_payouts must be false
        $this->assertFalse($voucher->has_payouts);

        $response = $this->makeReservationStoreRequest($voucher, $product);
        $reservation = ProductReservation::find($response->json('data.id'));

        $this->assertNotNull($reservation);
        $this->makeReservationAcceptRequest($reservation)->assertSuccessful();
        $this->assertEquals(1, $reservation->product_voucher->transactions()->count());

        /** @var VoucherTransaction $transaction */
        $transaction = $reservation->product_voucher->transactions()->first();
        $transaction->skipTransferDelay($sponsorOrganization->employees[0]);

        // has_payouts must be false because transaction doesn't have bulk
        $this->assertFalse($voucher->has_payouts);

        $bulkIds = VoucherTransactionBulk::buildBulksForOrganization($sponsorOrganization);
        $bulkModels = VoucherTransactionBulk::whereIn('id', $bulkIds)->get();

        $bulkModels->each(fn (VoucherTransactionBulk $bulk) => $bulk->setAcceptedBNG(null, false));

        $voucher->refresh();
        $this->assertTrue($voucher->has_payouts);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testVoucherHasPayoutsWithTransaction(): void
    {
        $sponsorOrganization = $this->makeTestOrganization($this->makeIdentity());
        $providerOrganization = $this->makeTestOrganization($this->makeIdentity());

        $fund = $this->makeTestFund($sponsorOrganization);
        $this->makeBankConnection($sponsorOrganization->refresh());
        $this->makeTestFundProvider($providerOrganization, $fund);

        $voucher = $fund->makeVoucher($this->makeIdentity());
        $address = $voucher->token_without_confirmation->address;

        // has_payouts must be false by default
        $this->assertFalse($voucher->has_payouts);

        $headers = $this->makeApiHeaders($providerOrganization->identity);
        $response = $this->post("/api/v1/platform/provider/vouchers/$address/transactions", [
            'note' => Str::random(),
            'amount' => round($voucher->amount_available / 2),
            'organization_id' => $providerOrganization->id,
        ], $headers);

        $response->assertSuccessful();

        $transaction = VoucherTransaction::find($response->json('data.id'));
        $this->assertNotNull($transaction);

        // has_payouts must be false while transaction doesn't have bulk
        $this->assertFalse($voucher->refresh()->has_payouts);

        $transaction->skipTransferDelay($sponsorOrganization->employees[0]);

        // has_payouts must be false while transaction has state success but doesn't have bulk
        $this->assertFalse($voucher->refresh()->has_payouts);

        $bulkIds = VoucherTransactionBulk::buildBulksForOrganization($sponsorOrganization);
        $bulkModels = VoucherTransactionBulk::whereIn('id', $bulkIds)->get();

        $bulkModels->each(fn (VoucherTransactionBulk $bulk) => $bulk->setAcceptedBNG(null, false));
        $this->assertTrue($voucher->refresh()->has_payouts);
    }
}
