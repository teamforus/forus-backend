<?php

namespace Tests\Feature;

use App\Models\Identity;
use App\Models\Organization;
use App\Models\Product;
use App\Models\ProductReservation;
use App\Models\Voucher;
use App\Models\VoucherTransaction;
use App\Models\VoucherTransactionBulk;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;
use Tests\Traits\MakesProductReservations;
use Tests\Traits\MakesTestBankConnections;
use Tests\Traits\MakesTestFunds;

class VoucherHasPayoutTest extends TestCase
{
    use MakesTestFunds;
    use DatabaseTransactions;
    use MakesProductReservations;
    use MakesTestBankConnections;

    /**
     * @return void
     * @throws \Throwable
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

        $originalAmount = (float) $voucher->amount_available;
        $reservation = $this->makeReservation($identity, $voucher, $product);
        $transaction = $this->acceptReservation($originalAmount, $reservation);

        // has_payouts must be false because transaction doesn't have bulk
        $this->assertFalse($voucher->has_payouts);

        $transactionsBulk = $this->createTransactionBulk($sponsorOrganization);

        $transaction->update([
            'state' => VoucherTransaction::STATE_SUCCESS,
            'voucher_transaction_bulk_id' => $transactionsBulk->id,
        ]);

        $voucher->refresh();
        $this->assertTrue($voucher->has_payouts);
    }

    /**
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

        /** @var VoucherTransaction $transaction */
        $transaction = VoucherTransaction::find($response->json('data.id'));
        $this->assertNotNull($transaction);

        // has_payouts must be false while transaction doesn't have bulk
        $this->assertFalse($voucher->refresh()->has_payouts);

        $transaction->update([
            'state' => VoucherTransaction::STATE_SUCCESS
        ]);

        // has_payouts must be false while transaction has state success but doesn't have bulk
        $this->assertFalse($voucher->refresh()->has_payouts);

        $transactionsBulk = $this->createTransactionBulk($sponsorOrganization);

        $transaction->update([
            'voucher_transaction_bulk_id' => $transactionsBulk->id,
        ]);

        $this->assertTrue($voucher->refresh()->has_payouts);
    }

    /**
     * @param Organization $organization
     * @return VoucherTransactionBulk
     */
    private function createTransactionBulk(Organization $organization): VoucherTransactionBulk
    {
        /** @var VoucherTransactionBulk $transactionsBulk */
        $defaultAccount = $organization->bank_connection_active->bank_connection_default_account;

        /** @var VoucherTransactionBulk $transactionsBulk */
        $transactionsBulk = $organization->bank_connection_active->voucher_transaction_bulks()->create([
            'state' => VoucherTransactionBulk::STATE_ACCEPTED,
            'monetary_account_id' => $defaultAccount->monetary_account_id,
            'monetary_account_iban' => $defaultAccount->monetary_account_iban,
            'monetary_account_name' => $defaultAccount->monetary_account_name,
        ]);

        return $transactionsBulk;
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

        $this->post('/api/v1/platform/product-reservations', [
            'user_note' => [],
            'voucher_id' => $voucher->id,
            'product_id' => $product->id
        ], $headers)->assertJsonValidationErrors([
            'first_name',
            'last_name',
            'user_note',
        ]);

        $response = $this->post('/api/v1/platform/product-reservations', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'user_note' => '',
            'voucher_id' => $voucher->id,
            'product_id' => $product->id
        ], $headers);

        $response->assertSuccessful();

        /** @var ProductReservation $reservation */
        $reservation = ProductReservation::find($response->json('data.id'));
        $this->assertNotNull($reservation);

        return $reservation;
    }

    /**
     * @param float $originalAmount
     * @param ProductReservation $reservation
     * @return VoucherTransaction
     */
    protected function acceptReservation(
        float $originalAmount,
        ProductReservation $reservation,
    ): VoucherTransaction {
        $startTime = now();

        $provider = $reservation->product->organization;
        $headers = $this->makeApiHeaders($this->makeIdentityProxy(
            $provider->employees->first()->identity,
        ));

        // accept reservation
        $acceptUrl = sprintf(
            '/api/v1/platform/organizations/%s/product-reservations/%s/accept',
            $provider->id,
            $reservation->id
        );

        $response = $this->post($acceptUrl, [], $headers);

        $response->assertSuccessful();
        $response->assertJsonFragment([
            'state' => ProductReservation::STATE_ACCEPTED
        ]);

        $this->assertSame((float) $reservation->voucher->amount_available, $originalAmount - $reservation->amount);

        /** @var ProductReservation $reservation */
        $reservation = ProductReservation::find($reservation->id);
        $this->assertTrue($reservation->isAccepted());

        // check transaction exists
        /** @var VoucherTransaction $transaction */
        $transaction = $reservation->product_voucher->transactions()
            ->where('created_at', '>=', $startTime)
            ->first();

        $this->assertNotNull($transaction);

        return $transaction;
    }
}
