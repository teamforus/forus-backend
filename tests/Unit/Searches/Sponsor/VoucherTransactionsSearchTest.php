<?php

namespace Tests\Unit\Searches\Sponsor;

use App\Models\Organization;
use App\Models\VoucherTransaction;
use App\Models\VoucherTransactionBulk;
use App\Scopes\Builders\VoucherTransactionQuery;
use App\Searches\VoucherTransactionsSearch;
use App\Traits\DoesTesting;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Tests\Traits\MakesProductReservations;
use Tests\Traits\MakesTestBankConnections;
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
    use MakesProductReservations;
    use MakesTestBankConnections;

    /**
     * @return void
     */
    public function testQueryBuilds(): void
    {
        $search = new VoucherTransactionsSearch([], VoucherTransaction::query());
        $organization = $this->makeTestOrganization($this->makeIdentity());

        $this->assertQueryBuilds($search->searchSponsor($organization));
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
        ], [$transaction1->id, $transaction2->id], $organization);

        $this->assertSearchIds([
            'fund_id' => $fund1->id,
        ], [$transaction1->id], $organization);

        $this->assertSearchIds([
            'fund_id' => $fund2->id,
        ], [$transaction2->id], $organization);
    }

    /**
     * @return void
     */
    public function testFiltersByVoucherId(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization);

        $provider = $this->makeTestProviderOrganization($this->makeIdentity());
        $product = $this->makeTestProduct($provider);

        $identity1 = $this->makeIdentity($this->makeUniqueEmail());
        $identity2 = $this->makeIdentity($this->makeUniqueEmail());

        $voucher1 = $this->makeTestVoucher($fund, $identity1);
        $voucher2 = $this->makeTestVoucher($fund, $identity2);

        $transactionArr = [
            'target' => VoucherTransaction::TARGET_PROVIDER,
            'amount' => $product->price,
            'product_id' => $product->id,
            'organization_id' => $product->organization_id,
        ];

        $transaction1 = $voucher1->makeTransaction($transactionArr);
        $transaction2 = $voucher2->makeTransaction($transactionArr);

        $this->assertSearchIds([
            'voucher_id' => $voucher1->id,
        ], [$transaction1->id], $organization);

        $this->assertSearchIds([
            'voucher_id' => $voucher2->id,
        ], [$transaction2->id], $organization);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testFiltersByVoucherTransactionBulkId(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());

        $this->makeTestImplementation($organization);
        $this->makeBankConnection($organization);

        // create first fund, transactions and draft bulk
        $fund1 = $this->makeTestFund($organization);
        $transaction1 = $this->makeTransactions($fund1, 1)[0];

        $bulkIds = VoucherTransactionBulk::buildBulksForOrganization($organization);
        $this->assertCount(1, $bulkIds);
        $bulk1 = VoucherTransactionBulk::whereIn('id', $bulkIds)->first();

        // create second fund, transactions and draft bulk
        $fund2 = $this->makeTestFund($organization);
        $transaction2 = $this->makeTransactions($fund2, 1)[0];

        $bulkIds = VoucherTransactionBulk::buildBulksForOrganization($organization);
        $this->assertCount(1, $bulkIds);
        $bulk2 = VoucherTransactionBulk::whereIn('id', $bulkIds)->first();

        $this->assertSearchIds([
            'voucher_transaction_bulk_id' => $bulk1->id,
        ], [$transaction1->id], $organization);

        $this->assertSearchIds([
            'voucher_transaction_bulk_id' => $bulk2->id,
        ], [$transaction2->id], $organization);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testFiltersByPendingBulking(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());

        $this->makeTestImplementation($organization);
        $this->makeBankConnection($organization);

        // create first fund, transactions with bulk
        $fund1 = $this->makeTestFund($organization);
        $transaction1 = $this->makeTransactions($fund1, 1)[0];

        $bulkIds = VoucherTransactionBulk::buildBulksForOrganization($organization);
        $this->assertCount(1, $bulkIds);

        // create second fund, transactions without bulk
        $fund2 = $this->makeTestFund($organization);
        $transaction2 = $this->makeTransactions($fund2, 1)[0];

        $this->assertSearchIds([], [$transaction1->id, $transaction2->id], $organization);
        $this->assertSearchIds(['pending_bulking' => true], [$transaction2->id], $organization);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testFiltersByExecutionDateFrom(): void
    {
        $now = Carbon::now();
        $organization = $this->makeTestOrganization($this->makeIdentity());

        $this->makeTestImplementation($organization);
        $this->makeBankConnection($organization);

        // create first fund, transactions and draft bulk
        $fund1 = $this->makeTestFund($organization);
        $transaction1 = $this->makeTransactions($fund1, 1)[0];

        $bulkIds = VoucherTransactionBulk::buildBulksForOrganization($organization);
        $this->assertCount(1, $bulkIds);
        $bulk1 = VoucherTransactionBulk::whereIn('id', $bulkIds)->first();
        $bulk1->update(['execution_date' => $now]);

        // create second fund, transactions and draft bulk
        $fund2 = $this->makeTestFund($organization);
        $transaction2 = $this->makeTransactions($fund2, 1)[0];

        $bulkIds = VoucherTransactionBulk::buildBulksForOrganization($organization);
        $this->assertCount(1, $bulkIds);
        $bulk2 = VoucherTransactionBulk::whereIn('id', $bulkIds)->first();
        $bulk2->update(['execution_date' => $now->copy()->addDays(5)]);

        // assert "from date" filter
        $this->assertSearchIds([
            'execution_date_from' => $now->format('Y-m-d'),
        ], [$transaction1->id, $transaction2->id], $organization);

        $this->assertSearchIds([
            'execution_date_from' => $now->copy()->addDays(3)->format('Y-m-d'),
        ], [$transaction2->id], $organization);

        // assert "to date" filter
        $this->assertSearchIds([
            'execution_date_to' => $now->copy()->addDays(6)->format('Y-m-d'),
        ], [$transaction1->id, $transaction2->id], $organization);

        $this->assertSearchIds([
            'execution_date_to' => $now->copy()->addDays(3)->format('Y-m-d'),
        ], [$transaction1->id], $organization);

        $this->assertSearchIds([
            'execution_date_from' => $now->format('Y-m-d'),
            'execution_date_to' => $now->copy()->addDays(3)->format('Y-m-d'),
        ], [$transaction1->id], $organization);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testFiltersByReservationVoucherId(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization);

        // make first voucher, product and transaction
        $voucher1 = $this->makeTestVoucher($fund, identity: $this->makeIdentity());
        $product1 = $this->makeTestProduct($this->makeTestProviderOrganization($this->makeIdentity()));

        $voucher1->makeTransaction([
            'target' => VoucherTransaction::TARGET_PROVIDER,
            'amount' => $product1->price,
            'product_id' => $product1->id,
            'organization_id' => $product1->organization_id,
        ]);

        // make second voucher, product and approved reservation with transaction
        $voucher2 = $this->makeTestVoucher($fund, identity: $this->makeIdentity());
        $product2 = $this->createProductForReservation($organization, [$fund]);
        $reservationTransaction = $this->makeReservation($voucher2, $product2)->acceptProvider()->voucher_transaction;

        $this->assertSearchIds([
            'reservation_voucher_id' => $voucher2->id,
        ], [$reservationTransaction->id], $organization);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testOrdersByPaymentType(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization);

        $provider = $this->makeTestProviderOrganization($this->makeIdentity());
        $product = $this->makeTestProduct($provider);

        $identity = $this->makeIdentity($this->makeUniqueEmail());
        $voucher = $this->makeTestVoucher($fund, $identity);

        $transactionA = $voucher->makeTransaction([
            'target' => VoucherTransaction::TARGET_PROVIDER,
            'amount' => $product->price,
            'product_id' => $product->id,
            'organization_id' => $product->organization_id,
            'description' => 'A description',
        ]);

        $transactionB = $voucher->makeTransaction([
            'target' => VoucherTransaction::TARGET_IBAN,
            'amount' => 10,
        ]);

        $this->assertSearchOrder([
            'identity_address' => $identity->address,
            'order_by' => 'payment_type',
            'order_dir' => 'asc',
        ], [$transactionA->id, $transactionB->id], $organization);

        $this->assertSearchOrder([
            'identity_address' => $identity->address,
            'order_by' => 'payment_type',
            'order_dir' => 'desc',
        ], [$transactionB->id, $transactionA->id], $organization);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testOrdersByRelation(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization);

        $identity = $this->makeIdentity($this->makeUniqueEmail());
        $voucher = $this->makeTestVoucher($fund, $identity);

        $transactionA = $voucher->makeTransaction([
            'target' => VoucherTransaction::TARGET_IBAN,
            'amount' => 10,
        ]);

        $transactionB = $voucher->makeTransaction([
            'target' => VoucherTransaction::TARGET_IBAN,
            'amount' => 10,
        ]);

        $transactionA->addPayoutRelation('email', $this->makeUniqueEmail('a_mail'));
        $transactionB->addPayoutRelation('email', $this->makeUniqueEmail('b_mail'));

        $this->assertSearchOrder([
            'identity_address' => $identity->address,
            'order_by' => 'relation',
            'order_dir' => 'asc',
        ], [$transactionA->id, $transactionB->id], $organization);

        $this->assertSearchOrder([
            'identity_address' => $identity->address,
            'order_by' => 'relation',
            'order_dir' => 'desc',
        ], [$transactionB->id, $transactionA->id], $organization);
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
     * @param Organization $organization
     * @return void
     */
    private function assertSearchIds(array $filters, array $expectedIds, Organization $organization): void
    {
        $expected = collect($expectedIds)->sort()->values()->toArray();
        $search = $this->makeSearch($filters);
        $actual = collect($search->searchSponsor($organization)->pluck('id')->toArray())->sort()->values()->toArray();

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
        $search = $this->makeSearch($filters);

        $actual = VoucherTransactionQuery::order(
            $search->searchSponsor($organization),
            Arr::get($filters, 'order_by'),
            Arr::get($filters, 'order_dir'),
        )->pluck('id')->toArray();

        $this->assertSame($expectedIds, $actual);
    }
}
