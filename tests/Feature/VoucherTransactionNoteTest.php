<?php

namespace Feature;

use App\Models\Fund;
use App\Models\Implementation;
use App\Models\Organization;
use App\Models\Voucher;
use App\Models\VoucherTransaction;
use App\Scopes\Builders\VoucherQuery;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Database\Eloquent\Builder;
use Tests\TestCase;
use Tests\Traits\MakesTestFunds;

class VoucherTransactionNoteTest extends TestCase
{
    use DatabaseTransactions, MakesTestFunds;

    /**
     * @var string
     */
    protected string $apiUrl = '/api/v1/platform/organizations/%s/%s/transactions';

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
        $organization = $this->getOrganization();
        $fund = $this->getFund($organization);
        $provider = $this->getProvider($fund);
        $voucher = $this->getVoucher($fund);

        $address = $voucher->token_without_confirmation->address;

        $headers = $this->makeApiHeaders($this->makeIdentityProxy($provider->identity));
        $response = $this->post("/api/v1/platform/provider/vouchers/$address/transactions", [
            'note' => $this->note,
            'amount' => round($voucher->amount_available / 2),
            'organization_id' => $provider->id,
        ], $headers);

        $response->assertSuccessful();

        $transaction = VoucherTransaction::find($response->json('data.id'));
        $this->assertNotNull($transaction);

        $this->checkNoteVisibility($provider, $transaction, 'provider');
        $this->checkNoteVisibility($organization, $transaction, 'sponsor', false);
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
        $organization = $this->getOrganization();
        $fund = $this->getFund($organization);
        $provider = $this->getProvider($fund);
        $voucher = $this->getVoucher($fund);

        $headers = $this->makeApiHeaders($this->makeIdentityProxy($organization->identity));
        $response = $this->post(sprintf($this->apiUrl, $organization->id, 'sponsor'), [
            'note' => $this->note,
            'amount' => round($voucher->amount_available / 2),
            'target' => VoucherTransaction::TARGET_PROVIDER,
            'voucher_id' => $voucher->id,
            'note_shared' => $share,
            'organization_id' => $provider->id,
        ], $headers);

        $response->assertSuccessful();

        $transaction = VoucherTransaction::find($response->json('data.id'));
        $this->assertNotNull($transaction);

        $this->checkNoteVisibility($organization, $transaction);
        $this->checkNoteVisibility($provider, $transaction, 'provider', $share);
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
        string $type = 'sponsor',
        bool $assertVisible = true
    ): void {
        $headers = $this->makeApiHeaders($this->makeIdentityProxy($organization->identity));
        $response = $this->getJson(
            sprintf($this->apiUrl, $organization->id, $type) . "/$transaction->address", $headers
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

    /**
     * @return Organization|null
     */
    private function getOrganization(): ?Organization
    {
        $implementation = Implementation::byKey($this->implementationName);
        $this->assertNotNull($implementation);
        $this->assertNotNull($implementation->organization);

        return $implementation->organization;
    }

    /**
     * @param Organization $organization
     * @return Fund
     */
    private function getFund(Organization $organization): Fund
    {
        /** @var Fund $fund */
        $fund = $organization->funds()->where('type', Fund::TYPE_BUDGET)->first();
        $this->assertNotNull($fund);

        return $fund;
    }

    /**
     * @param Fund $fund
     * @return Organization
     */
    private function getProvider(Fund $fund): Organization
    {
        /** @var Organization $provider */
        $provider = $fund->provider_organizations_approved()->first();
        $this->assertNotNull($provider);

        return $provider;
    }

    /**
     * @param Fund $fund
     * @return Voucher
     */
    private function getVoucher(Fund $fund): Voucher
    {
        /** @var Voucher $voucher */
        $voucher = $fund->vouchers()
            ->where(fn(Builder $builder) => VoucherQuery::whereNotExpiredAndActive($builder))
            ->where(fn(Builder $builder) => VoucherQuery::whereHasBalance($builder))
            ->whereNull('product_id')
            ->first();

        $this->assertNotNull($voucher);

        return $voucher;
    }
}
