<?php

namespace Tests\Unit\Searches;

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
use Tests\Traits\MakesTestOrganizationOffices;
use Tests\Traits\MakesTestOrganizations;
use Tests\Traits\MakesTestVouchers;
use Tests\Traits\MakesVoucherTransaction;
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
    use MakesTestOrganizationOffices;

    /**
     * @return void
     */
    public function testQueryBuilds(): void
    {
        $search = new VoucherTransactionsSearch([], VoucherTransaction::query());

        $this->assertQueryBuilds($search->query());
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testFiltersByQuery()
    {
        $fundNamePart1 = 'unique';
        $fundNamePart2 = 'other';

        $providerNamePart1 = 'interesting';
        $providerNamePart2 = 'sad';

        $productNamePart1 = 'first';
        $productNamePart2 = 'second';

        $branchNamePart1 = 'third';
        $branchNamePart2 = 'fifth';

        $branchNumberPart1 = '55555';
        $branchNumberPart2 = '66666';

        $branchIdPart1 = '55555';
        $branchIdPart2 = '66666';

        $uid1 = 'longer';
        $uid2 = 'shorter';

        $notePart1 = 'qwerty';
        $notePart2 = 'something';

        $organization = $this->makeTestOrganization($this->makeIdentity());

        $fund1 = $this->makeTestFund($organization, ['name' => "$fundNamePart1 fund name"]);
        $fund2 = $this->makeTestFund($organization, ['name' => "$fundNamePart2 fund name"]);

        $provider1 = $this->makeTestProviderOrganization($this->makeIdentity(), ['name' => "$providerNamePart1 fund name"]);
        $provider2 = $this->makeTestProviderOrganization($this->makeIdentity(), ['name' => "$providerNamePart2 fund name"]);

        $office1 = $this->makeOrganizationOffice($provider1, [
            'address' => 'address',
            'branch_id' => "{$branchIdPart1}2222",
            'branch_name' => "$branchNamePart1 branch name",
            'branch_number' => "{$branchNumberPart1}777777",
        ]);

        $office2 = $this->makeOrganizationOffice($provider2, [
            'address' => 'address',
            'branch_id' => "{$branchIdPart2}2222",
            'branch_name' => "$branchNamePart2 branch name",
            'branch_number' => "{$branchNumberPart2}777777",
        ]);

        $employee1 = $provider1->addEmployee($this->makeIdentity(), office_id: $office1->id);
        $employee2 = $provider2->addEmployee($this->makeIdentity(), office_id: $office2->id);

        // make identity
        $identity1 = $this->makeIdentity($this->makeUniqueEmail());
        $identity2 = $this->makeIdentity($this->makeUniqueEmail());

        // make vouchers
        $voucher1 = $this->makeTestVoucher($fund1, $identity1);
        $voucher2 = $this->makeTestVoucher($fund2, $identity2);

        $product1 = $this->createProductForReservation($provider1, [$fund1]);
        $product1->update(['name' => "$productNamePart1 product name"]);

        $product2 = $this->createProductForReservation($provider2, [$fund2]);
        $product2->update(['name' => "$productNamePart2 product name"]);

        // make pending reservations
        $reservation1 = $this->makeReservation($voucher1, $product1);
        $reservation2 = $this->makeReservation($voucher2, $product2);

        $transaction1 = $reservation1->acceptProvider($employee1)->voucher_transaction;
        $transaction1->update(['uid' => $uid1]);
        $transaction1->addNote('provider', "$notePart1 note");

        $transaction2 = $reservation2->acceptProvider($employee2)->voucher_transaction;
        $transaction2->update(['uid' => $uid2]);
        $transaction2->addNote('provider', "$notePart2 note");

        // assert search by fund name
        $this->assertSearchIds(['q' => $fundNamePart1], [$transaction1->id]);
        $this->assertSearchIds(['q' => $fundNamePart2], [$transaction2->id]);

        // assert search by provider name
        $this->assertSearchIds(['q' => $providerNamePart1], [$transaction1->id]);
        $this->assertSearchIds(['q' => $providerNamePart2], [$transaction2->id]);

        // assert search by product name
        $this->assertSearchIds(['q' => $productNamePart1], [$transaction1->id]);
        $this->assertSearchIds(['q' => $productNamePart2], [$transaction2->id]);

        // assert search by branch name
        $this->assertSearchIds(['q' => $branchNamePart1], [$transaction1->id]);
        $this->assertSearchIds(['q' => $branchNamePart2], [$transaction2->id]);

        // assert search by branch number
        $this->assertSearchIds(['q' => $branchNumberPart1], [$transaction1->id]);
        $this->assertSearchIds(['q' => $branchNumberPart2], [$transaction2->id]);

        // assert search by branch id
        $this->assertSearchIds(['q' => $branchIdPart1], [$transaction1->id]);
        $this->assertSearchIds(['q' => $branchIdPart2], [$transaction2->id]);

        // assert search by uid
        $this->assertSearchIds(['q' => $uid1], [$transaction1->id]);
        $this->assertSearchIds(['q' => $uid2], [$transaction2->id]);

        // assert search by transaction id
        $this->assertSearchIds(['q' => $transaction1->id], [$transaction1->id]);
        $this->assertSearchIds(['q' => $transaction2->id], [$transaction2->id]);

        // assert search by product id
        $this->assertSearchIds(['q' => $product1->id], [$transaction1->id]);
        $this->assertSearchIds(['q' => $product2->id], [$transaction2->id]);

        // assert search by provider note
        $this->assertSearchIds([
            'q' => $notePart1,
            'q_type' => 'provider',
        ], [$transaction1->id]);

        $this->assertSearchIds([
            'q' => $notePart2,
            'q_type' => 'provider',
        ], [$transaction2->id]);
    }

    /**
     * @return void
     */
    public function testFiltersByIdentityAddress(): void
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

        $this->assertSearchIds(['identity_address' => $identity1->address], [$transaction1->id]);
        $this->assertSearchIds(['identity_address' => $identity2->address], [$transaction2->id]);
    }

    /**
     * @return void
     */
    public function testFiltersByCreatedAt(): void
    {
        $now = Carbon::now();
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization);

        $provider = $this->makeTestProviderOrganization($this->makeIdentity());
        $product = $this->makeTestProduct($provider);

        $identity = $this->makeIdentity($this->makeUniqueEmail());
        $voucher = $this->makeTestVoucher($fund, $identity);

        $transactionArr = [
            'target' => VoucherTransaction::TARGET_PROVIDER,
            'amount' => $product->price,
            'product_id' => $product->id,
            'organization_id' => $product->organization_id,
        ];

        $transaction1 = $voucher->makeTransaction($transactionArr);

        Carbon::setTestNow($now->copy()->addDays(5));
        $transaction2 = $voucher->makeTransaction($transactionArr);

        // assert "from date" filter
        $this->assertSearchIds([
            'identity_address' => $identity->address,
            'from' => $now->format('Y-m-d'),
        ], [$transaction1->id, $transaction2->id]);

        $this->assertSearchIds([
            'identity_address' => $identity->address,
            'from' => $now->copy()->addDays(3)->format('Y-m-d'),
        ], [$transaction2->id]);

        // assert "to date" filter
        $this->assertSearchIds([
            'identity_address' => $identity->address,
            'to' => $now->copy()->addDays(6)->format('Y-m-d'),
        ], [$transaction1->id, $transaction2->id]);

        $this->assertSearchIds([
            'identity_address' => $identity->address,
            'to' => $now->copy()->addDays(3)->format('Y-m-d'),
        ], [$transaction1->id]);

        $this->assertSearchIds([
            'identity_address' => $identity->address,
            'from' => $now->format('Y-m-d'),
            'to' => $now->copy()->addDays(3)->format('Y-m-d'),
        ], [$transaction1->id]);
    }

    /**
     * @return void
     */
    public function testFiltersByFundState(): void
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

        $fund2->update(['state' => $fund2::STATE_CLOSED]);

        $this->assertSearchIds([
            'identity_address' => $identity->address,
            'fund_state' => $fund1::STATE_ACTIVE,
        ], [$transaction1->id]);

        $this->assertSearchIds([
            'identity_address' => $identity->address,
            'fund_state' => $fund2::STATE_CLOSED,
        ], [$transaction2->id]);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testFiltersByBulkState(): void
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

        // assert both transactions are visible with state draft
        $this->assertSearchIds([
            'bulk_state' => VoucherTransactionBulk::STATE_DRAFT,
        ], [$transaction1->id, $transaction2->id]);

        // accept first bulk set accepted and transactions filtered by bulk state 'accepted'
        // and second transaction still can be filtered by bulk state 'draft'
        $bulk1->setAcceptedBNG(null, false);

        $this->assertSearchIds([
            'bulk_state' => VoucherTransactionBulk::STATE_ACCEPTED,
        ], [$transaction1->id]);

        $this->assertSearchIds([
            'bulk_state' => VoucherTransactionBulk::STATE_DRAFT,
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
            'amount' => 10,
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

        // assert filter by targets type "top up"
        $this->assertSearchIds([
            'identity_address' => $identity->address,
            'targets' => [VoucherTransaction::TARGET_TOP_UP],
        ], [$transactionTopUp->id]);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testFiltersByState(): void
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

        // assert all transactions visible
        $this->assertSearchIds([
            'identity_address' => $identity->address,
        ], [$transaction1->id, $transaction2->id]);

        // assert all pending transactions visible
        $this->assertSearchIds([
            'identity_address' => $identity->address,
            'state' => VoucherTransaction::STATE_PENDING,
        ], [$transaction1->id, $transaction2->id]);

        // set second transaction as paid and assert transactions visibility by state
        $transaction2->setPaid(null, null);

        $this->assertSearchIds([
            'identity_address' => $identity->address,
            'state' => VoucherTransaction::STATE_PENDING,
        ], [$transaction1->id]);

        $this->assertSearchIds([
            'identity_address' => $identity->address,
            'state' => VoucherTransaction::STATE_SUCCESS,
        ], [$transaction2->id]);

        // set first transaction as canceled and assert transactions visibility by state
        $transaction1->cancelPending($organization->employees()->first(), true);

        $this->assertSearchIds([
            'identity_address' => $identity->address,
            'state' => VoucherTransaction::STATE_CANCELED,
        ], [$transaction1->id]);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testFiltersByAmount(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund1 = $this->makeTestFund($organization);
        $fund2 = $this->makeTestFund($organization);

        $provider = $this->makeTestProviderOrganization($this->makeIdentity());
        $product1 = $this->makeTestProduct($provider, 15);
        $product2 = $this->makeTestProduct($provider, 30);

        $identity = $this->makeIdentity($this->makeUniqueEmail());

        $voucher1 = $this->makeTestVoucher($fund1, $identity);
        $voucher2 = $this->makeTestVoucher($fund2, $identity);

        $transaction1 = $voucher1->makeTransaction([
            'target' => VoucherTransaction::TARGET_PROVIDER,
            'amount' => $product1->price,
            'product_id' => $product1->id,
            'organization_id' => $product1->organization_id,
        ]);

        $transaction2 = $voucher2->makeTransaction([
            'target' => VoucherTransaction::TARGET_PROVIDER,
            'amount' => $product2->price,
            'product_id' => $product2->id,
            'organization_id' => $product2->organization_id,
        ]);

        $this->assertSearchIds([
            'identity_address' => $identity->address,
            'amount_min' => 10,
        ], [$transaction1->id, $transaction2->id]);

        $this->assertSearchIds([
            'identity_address' => $identity->address,
            'amount_min' => 20,
        ], [$transaction2->id]);

        $this->assertSearchIds([
            'identity_address' => $identity->address,
            'amount_max' => 10,
        ], []);

        $this->assertSearchIds([
            'identity_address' => $identity->address,
            'amount_max' => 40,
        ], [$transaction1->id, $transaction2->id]);

        $this->assertSearchIds([
            'identity_address' => $identity->address,
            'amount_max' => 20,
        ], [$transaction1->id]);

        $this->assertSearchIds([
            'identity_address' => $identity->address,
            'amount_min' => 10,
            'amount_max' => 20,
        ], [$transaction1->id]);
    }

    /**
     * @return void
     */
    public function testFiltersByNonCancelable(): void
    {
        $now = Carbon::now();
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization);

        $provider = $this->makeTestProviderOrganization($this->makeIdentity());
        $product = $this->makeTestProduct($provider);

        $identity = $this->makeIdentity($this->makeUniqueEmail());
        $voucher = $this->makeTestVoucher($fund, $identity);

        $transactionArr = [
            'target' => VoucherTransaction::TARGET_PROVIDER,
            'amount' => $product->price,
            'product_id' => $product->id,
            'organization_id' => $product->organization_id,
        ];

        $transaction1 = $voucher->makeTransaction($transactionArr);

        Carbon::setTestNow($now->copy()->addDays(5));
        $transaction2 = $voucher->makeTransaction($transactionArr);

        // assert "from date" filter
        $this->assertSearchIds([
            'identity_address' => $identity->address,
            'non_cancelable_from' => $now->format('Y-m-d'),
        ], [$transaction1->id, $transaction2->id]);

        $this->assertSearchIds([
            'identity_address' => $identity->address,
            'non_cancelable_from' => $now->copy()->addDays(3)->format('Y-m-d'),
        ], [$transaction2->id]);

        // assert "to date" filter
        $this->assertSearchIds([
            'identity_address' => $identity->address,
            'non_cancelable_to' => $now->copy()->addDays(6)->format('Y-m-d'),
        ], [$transaction1->id, $transaction2->id]);

        $this->assertSearchIds([
            'identity_address' => $identity->address,
            'non_cancelable_to' => $now->copy()->addDays(3)->format('Y-m-d'),
        ], [$transaction1->id]);

        $this->assertSearchIds([
            'identity_address' => $identity->address,
            'non_cancelable_from' => $now->format('Y-m-d'),
            'non_cancelable_to' => $now->copy()->addDays(3)->format('Y-m-d'),
        ], [$transaction1->id]);
    }

    /**
     * @return void
     */
    public function testFiltersByTransferIn(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization);

        $provider = $this->makeTestProviderOrganization($this->makeIdentity());
        $product = $this->makeTestProduct($provider);

        $identity = $this->makeIdentity($this->makeUniqueEmail());
        $voucher = $this->makeTestVoucher($fund, $identity);

        $transactionArr = [
            'target' => VoucherTransaction::TARGET_PROVIDER,
            'amount' => $product->price,
            'product_id' => $product->id,
            'organization_id' => $product->organization_id,
        ];

        $transaction1 = $voucher->makeTransaction($transactionArr);
        $transaction1->update(['transfer_at' => Carbon::now()->addDays(5)]);

        $transaction2 = $voucher->makeTransaction($transactionArr);
        $transaction2->update(['transfer_at' => Carbon::now()->addDays(10)]);

        $this->assertSearchIds([
            'identity_address' => $identity->address,
            'transfer_in_min' => 2,
        ], [$transaction1->id, $transaction2->id]);

        $this->assertSearchIds([
            'identity_address' => $identity->address,
            'transfer_in_min' => 6,
        ], [$transaction2->id]);

        $this->assertSearchIds([
            'identity_address' => $identity->address,
            'transfer_in_max' => 15,
        ], [$transaction1->id, $transaction2->id]);

        $this->assertSearchIds([
            'identity_address' => $identity->address,
            'transfer_in_max' => 6,
        ], [$transaction1->id]);

        $this->assertSearchIds([
            'identity_address' => $identity->address,
            'transfer_in_min' => 3,
            'transfer_in_max' => 6,
        ], [$transaction1->id]);
    }

    /**
     * @return void
     */
    public function testOrdersById(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization);

        $provider = $this->makeTestProviderOrganization($this->makeIdentity());
        $product = $this->makeTestProduct($provider);

        $identity = $this->makeIdentity($this->makeUniqueEmail());
        $voucher = $this->makeTestVoucher($fund, $identity);

        $transactionArr = [
            'target' => VoucherTransaction::TARGET_PROVIDER,
            'amount' => $product->price,
            'product_id' => $product->id,
            'organization_id' => $product->organization_id,
        ];

        $transactionA = $voucher->makeTransaction($transactionArr);
        $transactionB = $voucher->makeTransaction($transactionArr);

        $this->assertSearchOrder([
            'identity_address' => $identity->address,
            'order_by' => 'id',
            'order_dir' => 'asc',
        ], [$transactionA->id, $transactionB->id]);

        $this->assertSearchOrder([
            'identity_address' => $identity->address,
            'order_by' => 'id',
            'order_dir' => 'desc',
        ], [$transactionB->id, $transactionA->id]);
    }

    /**
     * @return void
     */
    public function testOrdersByCreatedAt(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization);

        $provider = $this->makeTestProviderOrganization($this->makeIdentity());
        $product = $this->makeTestProduct($provider);

        $identity = $this->makeIdentity($this->makeUniqueEmail());
        $voucher = $this->makeTestVoucher($fund, $identity);

        $transactionArr = [
            'target' => VoucherTransaction::TARGET_PROVIDER,
            'amount' => $product->price,
            'product_id' => $product->id,
            'organization_id' => $product->organization_id,
        ];

        $transactionA = $voucher->makeTransaction($transactionArr);

        Carbon::setTestNow(Carbon::now()->addDays(5));
        $transactionB = $voucher->makeTransaction($transactionArr);

        $this->assertSearchOrder([
            'identity_address' => $identity->address,
            'order_by' => 'created_at',
            'order_dir' => 'asc',
        ], [$transactionA->id, $transactionB->id]);

        $this->assertSearchOrder([
            'identity_address' => $identity->address,
            'order_by' => 'created_at',
            'order_dir' => 'desc',
        ], [$transactionB->id, $transactionA->id]);
    }

    /**
     * @return void
     */
    public function testOrdersByAmount(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization);

        $provider = $this->makeTestProviderOrganization($this->makeIdentity());
        $product1 = $this->makeTestProduct($provider, 15);
        $product2 = $this->makeTestProduct($provider, 25);

        $identity = $this->makeIdentity($this->makeUniqueEmail());
        $voucher = $this->makeTestVoucher($fund, $identity);

        $transactionA = $voucher->makeTransaction([
            'target' => VoucherTransaction::TARGET_PROVIDER,
            'amount' => $product1->price,
            'product_id' => $product1->id,
            'organization_id' => $product1->organization_id,
        ]);

        $transactionB = $voucher->makeTransaction([
            'target' => VoucherTransaction::TARGET_PROVIDER,
            'amount' => $product2->price,
            'product_id' => $product2->id,
            'organization_id' => $product2->organization_id,
        ]);

        $this->assertSearchOrder([
            'identity_address' => $identity->address,
            'order_by' => 'amount',
            'order_dir' => 'asc',
        ], [$transactionA->id, $transactionB->id]);

        $this->assertSearchOrder([
            'identity_address' => $identity->address,
            'order_by' => 'amount',
            'order_dir' => 'desc',
        ], [$transactionB->id, $transactionA->id]);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testOrdersByState(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization);

        $provider = $this->makeTestProviderOrganization($this->makeIdentity());
        $product = $this->makeTestProduct($provider);

        $identity = $this->makeIdentity($this->makeUniqueEmail());
        $voucher = $this->makeTestVoucher($fund, $identity);

        $transactionArr = [
            'target' => VoucherTransaction::TARGET_PROVIDER,
            'amount' => $product->price,
            'product_id' => $product->id,
            'organization_id' => $product->organization_id,
        ];

        $transactionA = $voucher->makeTransaction($transactionArr);
        $transactionB = $voucher->makeTransaction($transactionArr);
        $transactionB->setPaid(null, null);

        $this->assertSearchOrder([
            'identity_address' => $identity->address,
            'order_by' => 'state',
            'order_dir' => 'asc',
        ], [$transactionA->id, $transactionB->id]);

        $this->assertSearchOrder([
            'identity_address' => $identity->address,
            'order_by' => 'state',
            'order_dir' => 'desc',
        ], [$transactionB->id, $transactionA->id]);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testOrdersByFundName(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund1 = $this->makeTestFund($organization, ['name' => 'A fund']);
        $fund2 = $this->makeTestFund($organization, ['name' => 'B fund']);

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

        $transactionA = $voucher1->makeTransaction($transactionArr);
        $transactionB = $voucher2->makeTransaction($transactionArr);

        $this->assertSearchOrder([
            'identity_address' => $identity->address,
            'order_by' => 'fund_name',
            'order_dir' => 'asc',
        ], [$transactionA->id, $transactionB->id]);

        $this->assertSearchOrder([
            'identity_address' => $identity->address,
            'order_by' => 'fund_name',
            'order_dir' => 'desc',
        ], [$transactionB->id, $transactionA->id]);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testOrdersByProviderName(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization);

        $provider1 = $this->makeTestProviderOrganization($this->makeIdentity(), ['name' => 'A org']);
        $provider2 = $this->makeTestProviderOrganization($this->makeIdentity(), ['name' => 'B org']);

        $product1 = $this->makeTestProduct($provider1);
        $product2 = $this->makeTestProduct($provider2);

        $identity = $this->makeIdentity($this->makeUniqueEmail());
        $voucher = $this->makeTestVoucher($fund, $identity);

        $transactionA = $voucher->makeTransaction([
            'target' => VoucherTransaction::TARGET_PROVIDER,
            'amount' => $product1->price,
            'product_id' => $product1->id,
            'organization_id' => $product1->organization_id,
        ]);

        $transactionB = $voucher->makeTransaction([
            'target' => VoucherTransaction::TARGET_PROVIDER,
            'amount' => $product2->price,
            'product_id' => $product2->id,
            'organization_id' => $product2->organization_id,
        ]);

        $this->assertSearchOrder([
            'identity_address' => $identity->address,
            'order_by' => 'provider_name',
            'order_dir' => 'asc',
        ], [$transactionA->id, $transactionB->id]);

        $this->assertSearchOrder([
            'identity_address' => $identity->address,
            'order_by' => 'provider_name',
            'order_dir' => 'desc',
        ], [$transactionB->id, $transactionA->id]);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testOrdersByProductName(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization);

        $provider = $this->makeTestProviderOrganization($this->makeIdentity());

        $product1 = $this->makeTestProduct($provider);
        $product1->update(['name' => 'A product']);

        $product2 = $this->makeTestProduct($provider);
        $product2->update(['name' => 'B product']);

        $identity = $this->makeIdentity($this->makeUniqueEmail());
        $voucher = $this->makeTestVoucher($fund, $identity);

        $transactionA = $voucher->makeTransaction([
            'target' => VoucherTransaction::TARGET_PROVIDER,
            'amount' => $product1->price,
            'product_id' => $product1->id,
            'organization_id' => $product1->organization_id,
        ]);

        $transactionB = $voucher->makeTransaction([
            'target' => VoucherTransaction::TARGET_PROVIDER,
            'amount' => $product2->price,
            'product_id' => $product2->id,
            'organization_id' => $product2->organization_id,
        ]);

        $this->assertSearchOrder([
            'identity_address' => $identity->address,
            'order_by' => 'product_name',
            'order_dir' => 'asc',
        ], [$transactionA->id, $transactionB->id]);

        $this->assertSearchOrder([
            'identity_address' => $identity->address,
            'order_by' => 'product_name',
            'order_dir' => 'desc',
        ], [$transactionB->id, $transactionA->id]);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testOrdersByTarget(): void
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
        ]);

        $transactionB = $voucher->makeTransaction([
            'target' => VoucherTransaction::TARGET_IBAN,
            'amount' => 10,
        ]);

        $this->assertSearchOrder([
            'identity_address' => $identity->address,
            'order_by' => 'target',
            'order_dir' => 'asc',
        ], [$transactionA->id, $transactionB->id]);

        $this->assertSearchOrder([
            'identity_address' => $identity->address,
            'order_by' => 'target',
            'order_dir' => 'desc',
        ], [$transactionB->id, $transactionA->id]);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testOrdersByUid(): void
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
            'uid' => 'A uid',
        ]);

        $transactionB = $voucher->makeTransaction([
            'target' => VoucherTransaction::TARGET_PROVIDER,
            'amount' => $product->price,
            'product_id' => $product->id,
            'organization_id' => $product->organization_id,
            'uid' => 'B uid',
        ]);

        $this->assertSearchOrder([
            'identity_address' => $identity->address,
            'order_by' => 'uid',
            'order_dir' => 'asc',
        ], [$transactionA->id, $transactionB->id]);

        $this->assertSearchOrder([
            'identity_address' => $identity->address,
            'order_by' => 'uid',
            'order_dir' => 'desc',
        ], [$transactionB->id, $transactionA->id]);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testOrdersByEmployeeEmail(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $employee1 = $organization->addEmployee($this->makeIdentity($this->makeUniqueEmail('a_employee')));
        $employee2 = $organization->addEmployee($this->makeIdentity($this->makeUniqueEmail('b_employee')));

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
            'employee_id' => $employee1->id,
        ]);

        $transactionB = $voucher->makeTransaction([
            'target' => VoucherTransaction::TARGET_PROVIDER,
            'amount' => $product->price,
            'product_id' => $product->id,
            'organization_id' => $product->organization_id,
            'employee_id' => $employee2->id,
        ]);

        $this->assertSearchOrder([
            'identity_address' => $identity->address,
            'order_by' => 'employee_email',
            'order_dir' => 'asc',
        ], [$transactionA->id, $transactionB->id]);

        $this->assertSearchOrder([
            'identity_address' => $identity->address,
            'order_by' => 'employee_email',
            'order_dir' => 'desc',
        ], [$transactionB->id, $transactionA->id]);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testOrdersByDescription(): void
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
            'target' => VoucherTransaction::TARGET_PROVIDER,
            'amount' => $product->price,
            'product_id' => $product->id,
            'organization_id' => $product->organization_id,
            'description' => 'B description',
        ]);

        $this->assertSearchOrder([
            'identity_address' => $identity->address,
            'order_by' => 'description',
            'order_dir' => 'asc',
        ], [$transactionA->id, $transactionB->id]);

        $this->assertSearchOrder([
            'identity_address' => $identity->address,
            'order_by' => 'description',
            'order_dir' => 'desc',
        ], [$transactionB->id, $transactionA->id]);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testOrdersByTargetIban(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization);

        $identity = $this->makeIdentity($this->makeUniqueEmail());
        $voucher = $this->makeTestVoucher($fund, $identity);

        $transactionA = $voucher->makeTransaction([
            'target' => VoucherTransaction::TARGET_IBAN,
            'amount' => 10,
            'target_iban' => 'A_IBAN',
        ]);

        $transactionB = $voucher->makeTransaction([
            'target' => VoucherTransaction::TARGET_IBAN,
            'amount' => 10,
            'target_iban' => 'B_IBAN',
        ]);

        $this->assertSearchOrder([
            'identity_address' => $identity->address,
            'order_by' => 'target_iban',
            'order_dir' => 'asc',
        ], [$transactionA->id, $transactionB->id]);

        $this->assertSearchOrder([
            'identity_address' => $identity->address,
            'order_by' => 'target_iban',
            'order_dir' => 'desc',
        ], [$transactionB->id, $transactionA->id]);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testOrdersByTransferAt(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization);

        $identity = $this->makeIdentity($this->makeUniqueEmail());
        $voucher = $this->makeTestVoucher($fund, $identity);

        $transactionA = $voucher->makeTransaction([
            'target' => VoucherTransaction::TARGET_IBAN,
            'amount' => 10,
            'transfer_at' => Carbon::now(),
        ]);

        $transactionB = $voucher->makeTransaction([
            'target' => VoucherTransaction::TARGET_IBAN,
            'amount' => 10,
            'transfer_at' => Carbon::now()->addDay(),
        ]);

        $this->assertSearchOrder([
            'identity_address' => $identity->address,
            'order_by' => 'transfer_at',
            'order_dir' => 'asc',
        ], [$transactionA->id, $transactionB->id]);

        $this->assertSearchOrder([
            'identity_address' => $identity->address,
            'order_by' => 'transfer_at',
            'order_dir' => 'desc',
        ], [$transactionB->id, $transactionA->id]);

        // date_non_cancelable using same field
        $this->assertSearchOrder([
            'identity_address' => $identity->address,
            'order_by' => 'date_non_cancelable',
            'order_dir' => 'asc',
        ], [$transactionA->id, $transactionB->id]);

        $this->assertSearchOrder([
            'identity_address' => $identity->address,
            'order_by' => 'date_non_cancelable',
            'order_dir' => 'desc',
        ], [$transactionB->id, $transactionA->id]);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testOrdersByTransferIn(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization);

        $identity = $this->makeIdentity($this->makeUniqueEmail());
        $voucher = $this->makeTestVoucher($fund, $identity);

        $transactionA = $voucher->makeTransaction([
            'target' => VoucherTransaction::TARGET_IBAN,
            'amount' => 10,
            'transfer_at' => Carbon::now(),
        ]);

        $transactionB = $voucher->makeTransaction([
            'target' => VoucherTransaction::TARGET_IBAN,
            'amount' => 10,
            'transfer_at' => Carbon::now()->addDay(),
        ]);

        $this->assertSearchOrder([
            'identity_address' => $identity->address,
            'order_by' => 'transfer_in',
            'order_dir' => 'asc',
        ], [$transactionA->id, $transactionB->id]);

        $this->assertSearchOrder([
            'identity_address' => $identity->address,
            'order_by' => 'transfer_in',
            'order_dir' => 'desc',
        ], [$transactionB->id, $transactionA->id]);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testOrdersByBulkState(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());

        $this->makeTestImplementation($organization);
        $this->makeBankConnection($organization);
        $fund = $this->makeTestFund($organization);
        $identity = $this->makeIdentity($this->makeUniqueEmail());

        // create first transactions and draft bulk
        $transactionA = $this->makeTransactions($fund, 1, identity: $identity)[0];

        $bulkIds = VoucherTransactionBulk::buildBulksForOrganization($organization);
        $this->assertCount(1, $bulkIds);
        $bulk1 = VoucherTransactionBulk::whereIn('id', $bulkIds)->first();
        $bulk1->setAcceptedBNG(null, false);

        // create second transactions and draft bulk
        $transactionB = $this->makeTransactions($fund, 1, identity: $identity)[0];

        $bulkIds = VoucherTransactionBulk::buildBulksForOrganization($organization);
        $this->assertCount(1, $bulkIds);

        $this->assertSearchOrder([
            'identity_address' => $identity->address,
            'order_by' => 'bulk_state',
            'order_dir' => 'asc',
        ], [$transactionA->id, $transactionB->id]);

        $this->assertSearchOrder([
            'identity_address' => $identity->address,
            'order_by' => 'bulk_state',
            'order_dir' => 'desc',
        ], [$transactionB->id, $transactionA->id]);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testOrdersByBulkId(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());

        $this->makeTestImplementation($organization);
        $this->makeBankConnection($organization);
        $fund = $this->makeTestFund($organization);
        $identity = $this->makeIdentity($this->makeUniqueEmail());

        // create first transactions and draft bulk
        $transactionA = $this->makeTransactions($fund, 1, identity: $identity)[0];
        $bulkIds = VoucherTransactionBulk::buildBulksForOrganization($organization);
        $this->assertCount(1, $bulkIds);

        // create second transactions and draft bulk
        $transactionB = $this->makeTransactions($fund, 1, identity: $identity)[0];
        $bulkIds = VoucherTransactionBulk::buildBulksForOrganization($organization);
        $this->assertCount(1, $bulkIds);

        $this->assertSearchOrder([
            'identity_address' => $identity->address,
            'order_by' => 'bulk_id',
            'order_dir' => 'asc',
        ], [$transactionA->id, $transactionB->id]);

        $this->assertSearchOrder([
            'identity_address' => $identity->address,
            'order_by' => 'bulk_id',
            'order_dir' => 'desc',
        ], [$transactionB->id, $transactionA->id]);
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
        $actual = collect($search->query()->pluck('id')->toArray())->sort()->values()->toArray();

        $this->assertSame($expected, $actual);
    }

    /**
     * @param array $filters
     * @param array $expectedIds
     * @return void
     */
    private function assertSearchOrder(array $filters, array $expectedIds): void
    {
        $search = $this->makeSearch($filters);

        $actual = VoucherTransactionQuery::order(
            $search->query(),
            Arr::get($filters, 'order_by'),
            Arr::get($filters, 'order_dir'),
        )->pluck('id')->toArray();

        $this->assertSame($expectedIds, $actual);
    }
}
