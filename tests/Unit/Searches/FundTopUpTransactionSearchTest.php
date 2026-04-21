<?php

namespace Tests\Unit\Searches;

use App\Models\BankConnectionAccount;
use App\Models\Fund;
use App\Models\FundTopUpTransaction;
use App\Models\Organization;
use App\Searches\FundTopUpTransactionSearch;
use App\Traits\DoesTesting;
use Illuminate\Support\Carbon;;
use Tests\Traits\MakesTestBankConnections;
use Tests\Traits\MakesTestFundRequests;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestOrganizations;

class FundTopUpTransactionSearchTest extends SearchTestCase
{
    use DoesTesting;
    use MakesTestFunds;
    use MakesTestFundRequests;
    use MakesTestOrganizations;
    use MakesTestBankConnections;

    /**
     * @return void
     */
    public function testQueryBuilds(): void
    {
        $search = new FundTopUpTransactionSearch([], FundTopUpTransaction::query());

        $this->assertQueryBuilds($search->query());
    }

    /**
     * @return void
     */
    public function testFiltersByQuery(): void
    {
        $ibanPart1 = 'unique';
        $ibanPart2 = 'other';

        $codePart1 = 'first';
        $codePart2 = 'second';

        $organization = $this->makeTestOrganization($this->makeIdentity());

        $fund1 = $this->makeTestFund($organization);
        $fund1->top_ups()->delete();

        $fund2 = $this->makeTestFund($organization);
        $fund2->top_ups()->delete();

        $bankConnection1 = $this->makeBankConnection($organization);
        $account1 = $bankConnection1->bank_connection_accounts()->first();
        $account1->update(['monetary_account_iban' => "$ibanPart1 iban"]);

        $transaction1 = $this->prepareTopUpTransaction($fund1, $account1, "{$codePart1}_code");

        $bankConnection2 = $this->makeBankConnection($organization);
        $account2 = $bankConnection2->bank_connection_accounts()->first();
        $account2->update(['monetary_account_iban' => "$ibanPart2 iban"]);

        $transaction2 = $this->prepareTopUpTransaction($fund2, $account2, "{$codePart2}_code");

        $this->assertSearchIds(['q' => $ibanPart1], [$transaction1->id], $organization);
        $this->assertSearchIds(['q' => $codePart1], [$transaction1->id], $organization);

        $this->assertSearchIds(['q' => $ibanPart2], [$transaction2->id], $organization);
        $this->assertSearchIds(['q' => $codePart2], [$transaction2->id], $organization);
    }

    /**
     * @return void
     */
    public function testFiltersByAmount(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());

        $fund = $this->makeTestFund($organization);
        $fund->top_ups()->delete();

        $bankConnection = $this->makeBankConnection($organization);
        $account = $bankConnection->bank_connection_accounts()->first();

        $transactionA = $this->prepareTopUpTransaction($fund, $account, amount: 500);
        $transactionB = $this->prepareTopUpTransaction($fund, $account, amount: 1000);

        $this->assertSearchIds(['amount_min' => 400], [$transactionA->id, $transactionB->id], $organization);
        $this->assertSearchIds(['amount_min' => 600], [$transactionB->id], $organization);
        $this->assertSearchIds(['amount_max' => 1200], [$transactionA->id, $transactionB->id], $organization);
        $this->assertSearchIds(['amount_max' => 600], [$transactionA->id], $organization);
    }

    /**
     * @return void
     */
    public function testFiltersByCreatedAt(): void
    {
        $now = Carbon::now();
        $identity = $this->makeIdentity($this->makeUniqueEmail());
        $organization = $this->makeTestOrganization($identity);

        $fund = $this->makeTestFund($organization);
        $fund->top_ups()->delete();

        $bankConnection = $this->makeBankConnection($organization);
        $account = $bankConnection->bank_connection_accounts()->first();

        $transaction1 = $this->prepareTopUpTransaction($fund, $account);

        Carbon::setTestNow($now->copy()->addDays(5));
        $transaction2 = $this->prepareTopUpTransaction($fund, $account);

        // assert "from date" filter
        $this->assertSearchIds([
            'from' => $now->format('Y-m-d'),
        ], [$transaction1->id, $transaction2->id], $organization);

        $this->assertSearchIds([
            'from' => $now->copy()->addDays(3)->format('Y-m-d'),
        ], [$transaction2->id], $organization);

        // assert "to date" filter
        $this->assertSearchIds([
            'to' => $now->copy()->addDays(6)->format('Y-m-d'),
        ], [$transaction1->id, $transaction2->id], $organization);

        $this->assertSearchIds([
            'to' => $now->copy()->addDays(3)->format('Y-m-d'),
        ], [$transaction1->id], $organization);
    }

    /**
     * @return void
     */
    public function testOrdersByCreatedAt(): void
    {
        $now = Carbon::now();
        $identity = $this->makeIdentity($this->makeUniqueEmail());
        $organization = $this->makeTestOrganization($identity);

        $fund = $this->makeTestFund($organization);
        $fund->top_ups()->delete();

        $bankConnection = $this->makeBankConnection($organization);
        $account = $bankConnection->bank_connection_accounts()->first();

        $olderTransaction = $this->prepareTopUpTransaction($fund, $account);

        Carbon::setTestNow($now->copy()->addDays(5));
        $newerTransaction = $this->prepareTopUpTransaction($fund, $account);

        $this->assertSearchOrder([
            'order_by' => 'created_at',
            'order_dir' => 'asc',
        ], [$olderTransaction->id, $newerTransaction->id], $organization);

        $this->assertSearchOrder([
            'order_by' => 'created_at',
            'order_dir' => 'desc',
        ], [$newerTransaction->id, $olderTransaction->id], $organization);
    }

    /**
     * @return void
     */
    public function testOrdersByAmount(): void
    {
        $identity = $this->makeIdentity($this->makeUniqueEmail());
        $organization = $this->makeTestOrganization($identity);

        $fund = $this->makeTestFund($organization);
        $fund->top_ups()->delete();

        $bankConnection = $this->makeBankConnection($organization);
        $account = $bankConnection->bank_connection_accounts()->first();

        $higherTransaction = $this->prepareTopUpTransaction($fund, $account, amount: 200);
        $lowerTransaction = $this->prepareTopUpTransaction($fund, $account, amount: 100);

        $this->assertSearchOrder([
            'order_by' => 'amount',
            'order_dir' => 'asc',
        ], [$lowerTransaction->id, $higherTransaction->id], $organization);

        $this->assertSearchOrder([
            'order_by' => 'amount',
            'order_dir' => 'desc',
        ], [$higherTransaction->id, $lowerTransaction->id], $organization);
    }

    /**
     * @return void
     */
    public function testOrdersByCodeAndIban(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());

        $fund1 = $this->makeTestFund($organization);
        $fund1->top_ups()->delete();

        $fund2 = $this->makeTestFund($organization);
        $fund2->top_ups()->delete();

        $bankConnectionA = $this->makeBankConnection($organization);
        $accountA = $bankConnectionA->bank_connection_accounts()->first();
        $accountA->update(['monetary_account_iban' => 'A iban']);
        $transactionA = $this->prepareTopUpTransaction($fund1, $accountA, 'a_code');

        $bankConnectionB = $this->makeBankConnection($organization);
        $accountB = $bankConnectionB->bank_connection_accounts()->first();
        $accountB->update(['monetary_account_iban' => 'B iban']);
        $transactionB = $this->prepareTopUpTransaction($fund2, $accountB, 'b_code');

        // order by code
        $this->assertSearchOrder([
            'order_by' => 'code',
            'order_dir' => 'asc',
        ], [$transactionA->id, $transactionB->id], $organization);

        $this->assertSearchOrder([
            'order_by' => 'code',
            'order_dir' => 'desc',
        ], [$transactionB->id, $transactionA->id], $organization);

        // order by IBAN
        $this->assertSearchOrder([
            'order_by' => 'iban',
            'order_dir' => 'asc',
        ], [$transactionA->id, $transactionB->id], $organization);

        $this->assertSearchOrder([
            'order_by' => 'iban',
            'order_dir' => 'desc',
        ], [$transactionB->id, $transactionA->id], $organization);
    }

    /**
     * @param Fund $fund
     * @param BankConnectionAccount $account
     * @param string|null $code
     * @param int|null $amount
     * @return FundTopUpTransaction
     */
    protected function prepareTopUpTransaction(
        Fund $fund,
        BankConnectionAccount $account,
        string $code = null,
        int $amount = null,
    ): FundTopUpTransaction {
        $topUp = $fund->getOrCreateTopUp();
        $code && $topUp->update(['code' => $code]);

        $transaction = $topUp->transactions()->create(['amount' => $amount ?? 10000]);
        $transaction->forceFill(['bank_connection_account_id' => $account->id])->save();

        return $transaction;
    }

    /**
     * @param array $filters
     * @param Organization $organization
     * @return FundTopUpTransactionSearch
     */
    private function makeSearch(array $filters, Organization $organization): FundTopUpTransactionSearch
    {
        $query = FundTopUpTransaction::query()
            ->whereRelation('fund_top_up.fund', 'organization_id', $organization->id);

        return new FundTopUpTransactionSearch($filters, $query);
    }

    /**
     * @param array $filters
     * @param array $expectedIds
     * @param Organization $organization
     * @return void
     */
    private function assertSearchIds(array $filters, array $expectedIds, Organization $organization): void
    {
        $expected = collect($expectedIds)->sort()->values()->toArray();
        $search = $this->makeSearch($filters, $organization);
        $actual = collect($search->query()->pluck('id')->toArray())->sort()->values()->toArray();

        $this->assertSame($expected, $actual);
    }

    /**
     * @param array $filters
     * @param array $expectedIds
     * @param Organization $organization
     * @return void
     */
    private function assertSearchOrder(array $filters, array $expectedIds, Organization $organization): void
    {
        $search = $this->makeSearch($filters, $organization);
        $actual = $search->query()->pluck('id')->toArray();

        $this->assertSame($expectedIds, $actual);
    }
}
