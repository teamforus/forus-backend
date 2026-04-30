<?php

namespace Tests\Unit\Searches\Provider;

use App\Models\VoucherTransaction;
use App\Searches\VoucherTransactionsSearch;
use App\Traits\DoesTesting;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestOrganizations;
use Tests\Traits\MakesTestVouchers;
use Tests\Traits\MakesVoucherTransaction;
use Tests\Unit\Searches\SearchTestCase;
use Throwable;

class VoucherTransactionsSearchTest extends SearchTestCase
{
    use DoesTesting;
    use MakesTestFunds;
    use MakesTestVouchers;
    use MakesTestOrganizations;
    use MakesVoucherTransaction;

    /**
     * @return void
     */
    public function testQueryBuilds(): void
    {
        $search = new VoucherTransactionsSearch([], VoucherTransaction::query());

        $this->assertQueryBuilds($search->searchProvider());
    }

    /**
     * @return void
     */
    public function testFiltersByFundId(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund1 = $this->makeTestFund($organization);
        $fund2 = $this->makeTestFund($organization);

        $provider = $this->makeTestProviderOrganization($this->makeIdentity());
        $product = $this->makeTestProduct($provider);

        $identity = $this->makeIdentity($this->makeUniqueEmail());

        $voucher1 = $this->makeTestVoucher($fund1, $identity);
        $voucher2 = $this->makeTestVoucher($fund2, $identity);

        $transactionArr = [
            'target' => VoucherTransaction::TARGET_PROVIDER,
            'amount' => $product->price,
            'product_id' => $product->id,
            'organization_id' => $product->organization_id,
        ];

        $transaction1 = $voucher1->makeTransaction($transactionArr);
        $transaction2 = $voucher2->makeTransaction($transactionArr);

        $this->assertSearchIds([
            'identity_address' => $identity->address,
        ], [$transaction1->id, $transaction2->id]);

        $this->assertSearchIds([
            'identity_address' => $identity->address,
            'fund_id' => $fund1->id,
        ], [$transaction1->id]);

        $this->assertSearchIds([
            'identity_address' => $identity->address,
            'fund_id' => $fund2->id,
        ], [$transaction2->id]);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testFiltersByTargets(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund1 = $this->makeTestFund($organization, fundConfigsData: ['allow_voucher_top_ups' => true]);
        $fund2 = $this->makeTestFund($organization);

        $provider = $this->makeTestProviderOrganization($this->makeIdentity());
        $product = $this->makeTestProduct($provider);

        $identity = $this->makeIdentity($this->makeUniqueEmail());

        $voucher1 = $this->makeTestVoucher($fund1, $identity);
        $voucher2 = $this->makeTestVoucher($fund2, $identity);

        $providerTransaction = $voucher1->makeTransaction([
            'target' => VoucherTransaction::TARGET_PROVIDER,
            'amount' => $product->price,
            'product_id' => $product->id,
            'organization_id' => $product->organization_id,
        ]);

        $ibanTransaction = $voucher2->makeTransaction([
            'target' => VoucherTransaction::TARGET_IBAN,
            'amount' => $product->price,
            'product_id' => $product->id,
            'organization_id' => $product->organization_id,
        ]);

        $transactionTopUp = $this->makeTopUp($voucher1);
        $this->assertNotNull($transactionTopUp);

        // assert without "targets" filter only outgoing transactions visible
        $this->assertSearchIds([
            'identity_address' => $identity->address,
        ], [$providerTransaction->id, $ibanTransaction->id]);

        // assert filter by targets type "provider"
        $this->assertSearchIds([
            'identity_address' => $identity->address,
            'targets' => [VoucherTransaction::TARGET_PROVIDER],
        ], [$providerTransaction->id]);

        // assert filter by targets type "iban"
        $this->assertSearchIds([
            'identity_address' => $identity->address,
            'targets' => [VoucherTransaction::TARGET_IBAN],
        ], [$ibanTransaction->id]);

        // assert filter by targets type "top up" doesn't return top up for provider search
        $this->assertSearchIds([
            'identity_address' => $identity->address,
            'targets' => [VoucherTransaction::TARGET_TOP_UP],
        ], []);
    }

    /**
     * @param array $filters
     * @return VoucherTransactionsSearch
     */
    private function makeSearch(array $filters): VoucherTransactionsSearch
    {
        return new VoucherTransactionsSearch($filters, VoucherTransaction::query());
    }

    /**
     * @param array $filters
     * @param array $expectedIds
     * @return void
     */
    private function assertSearchIds(array $filters, array $expectedIds): void
    {
        $expected = collect($expectedIds)->sort()->values()->toArray();
        $search = $this->makeSearch($filters);
        $actual = collect($search->searchProvider()->pluck('id')->toArray())->sort()->values()->toArray();

        $this->assertSame($expected, $actual);
    }
}
