<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\VoucherTransaction;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tests\Traits\MakesTestFundProviders;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestOrganizations;

class VoucherTransactionNoteTest extends TestCase
{
    use MakesTestFunds;
    use DatabaseTransactions;
    use MakesTestOrganizations;
    use MakesTestFundProviders;

    /**
     * @var string
     */
    protected string $implementationName = 'nijmegen';

    /**
     * @var string
     */
    protected string $note = 'Test note';

    /**
     * @return void
     */
    public function testCheckTransactionNoteByProvider(): void
    {
        $sponsorOrganization = $this->makeTestOrganization($this->makeIdentity());
        $providerOrganization = $this->makeTestOrganization($this->makeIdentity());

        $fund = $this->makeTestFund($sponsorOrganization);
        $this->makeTestFundProvider($providerOrganization, $fund);

        $voucher = $fund->makeVoucher($this->makeIdentity());
        $address = $voucher->token_without_confirmation->address;

        $headers = $this->makeApiHeaders($this->makeIdentityProxy($providerOrganization->identity));
        $response = $this->post("/api/v1/platform/provider/vouchers/$address/transactions", [
            'note' => $this->note,
            'amount' => round($voucher->amount_available / 2),
            'organization_id' => $providerOrganization->id,
        ], $headers);

        $response->assertSuccessful();

        $transaction = VoucherTransaction::find($response->json('data.id'));
        $this->assertNotNull($transaction);

        $this->checkNoteVisibility($providerOrganization, $transaction, 'provider', true);
        $this->checkNoteVisibility($sponsorOrganization, $transaction, 'sponsor', false);
    }

    /**
     * @return void
     */
    public function testCheckTransactionNoteBySponsor(): void
    {
        $this->makeTransactionNoteBySponsor(false);
    }

    /**
     * @return void
     */
    public function testCheckTransactionNoteBySponsorShared(): void
    {
        $this->makeTransactionNoteBySponsor();
    }

    /**
     * @param bool $share
     * @return void
     */
    private function makeTransactionNoteBySponsor(bool $share = true): void
    {
        $sponsorOrganization = $this->makeTestOrganization($this->makeIdentity());
        $providerOrganization = $this->makeTestOrganization($this->makeIdentity());

        $fund = $this->makeTestFund($sponsorOrganization);
        $this->makeTestFundProvider($providerOrganization, $fund);
        $voucher = $fund->makeVoucher($this->makeIdentity());

        $headers = $this->makeApiHeaders($this->makeIdentityProxy($sponsorOrganization->identity));
        $response = $this->post("/api/v1/platform/organizations/$sponsorOrganization->id/sponsor/transactions", [
            'note' => $this->note,
            'amount' => round($voucher->amount_available / 2),
            'target' => VoucherTransaction::TARGET_PROVIDER,
            'voucher_id' => $voucher->id,
            'note_shared' => $share,
            'organization_id' => $providerOrganization->id,
        ], $headers);

        $response->assertSuccessful();

        $transaction = VoucherTransaction::find($response->json('data.id'));
        $this->assertNotNull($transaction);

        $this->checkNoteVisibility($sponsorOrganization, $transaction, 'sponsor', true);
        $this->checkNoteVisibility($providerOrganization, $transaction, 'provider', $share);
    }

    /**
     * @param Organization $organization
     * @param VoucherTransaction $transaction
     * @param string $type
     * @param bool $assertVisible
     * @return void
     */
    private function checkNoteVisibility(
        Organization $organization,
        VoucherTransaction $transaction,
        string $type,
        bool $assertVisible
    ): void {
        $response = $this->getJson(
            "/api/v1/platform/organizations/$organization->id/$type/transactions/$transaction->address",
            $this->makeApiHeaders($this->makeIdentityProxy($organization->identity)),
        );

        $response->assertSuccessful();
        $note = $response->json('data.notes.0');

        if ($assertVisible) {
            $this->assertNotEmpty($note);
            $this->assertEquals($note['message'], $this->note);
        } else {
            $this->assertEmpty($note);
        }
    }
}
