<?php

namespace Tests\Browser;

use App\Models\Employee;
use App\Models\Fund;
use App\Models\Implementation;
use App\Models\Organization;
use App\Models\Product;
use App\Models\Role;
use App\Models\Voucher;
use App\Models\VoucherTransaction;
use Facebook\WebDriver\Exception\TimeOutException;
use Illuminate\Database\Eloquent\Collection;
use Laravel\Dusk\Browser;
use Tests\Browser\Traits\HasFrontendActions;
use Tests\Browser\Traits\RollbackModelsTrait;
use Tests\DuskTestCase;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestOrganizationOffices;
use Throwable;

class VoucherTransactionsProviderSearchTest extends DuskTestCase
{
    use MakesTestFunds;
    use HasFrontendActions;
    use RollbackModelsTrait;
    use MakesTestOrganizationOffices;

    /**
     * @throws Throwable
     * @return void
     */
    public function testVoucherTransactionsProviderSearch(): void
    {
        $implementation = Implementation::byKey('nijmegen');
        $organization = $implementation->organization;

        $transactionCount = 2;
        $fund = $this->makeTestFund($organization);
        $transaction = $this->prepareData($fund, $transactionCount);

        $providerOrganization = $transaction->product->organization;
        $this->assertNotNull($providerOrganization);

        $this->rollbackModels([], function () use (
            $implementation,
            $providerOrganization,
            $transaction,
            $transactionCount
        ) {
            $this->browse(function (Browser $browser) use (
                $implementation,
                $providerOrganization,
                $transaction,
                $transactionCount
            ) {
                $browser->visit($implementation->urlProviderDashboard());

                // Authorize identity
                $this->loginIdentity($browser, $providerOrganization->identity);
                $this->assertIdentityAuthenticatedOnProviderDashboard($browser, $providerOrganization->identity);
                $this->selectDashboardOrganization($browser, $providerOrganization);

                // Go to list and assert search
                $this->goToListPage($browser);
                $this->searchTransaction($browser, $transaction, $transactionCount, 'provider');

                // Logout
                $this->logout($browser);
            });
        }, function () use ($fund) {
            $fund->organization
                ->employees()
                ->whereNotIn('identity_address', [$fund->organization->identity_address])
                ->delete();

            $fund && $this->deleteFund($fund);
        });
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testVoucherTransactionsSponsorSearch(): void
    {
        $implementation = Implementation::byKey('nijmegen');
        $organization = $implementation->organization;

        $transactionCount = 2;
        $fund = $this->makeTestFund($organization);
        $transaction = $this->prepareData($fund, $transactionCount);

        $this->rollbackModels([], function () use (
            $implementation,
            $transaction,
            $transactionCount
        ) {
            $this->browse(function (Browser $browser) use (
                $implementation,
                $transaction,
                $transactionCount
            ) {
                $organization = $implementation->organization;
                $browser->visit($implementation->urlSponsorDashboard());

                // Authorize identity
                $this->loginIdentity($browser, $organization->identity);
                $this->assertIdentityAuthenticatedOnSponsorDashboard($browser, $organization->identity);
                $this->selectDashboardOrganization($browser, $organization);

                // Go to list and assert search
                $this->goToListPage($browser);
                $this->searchTransaction($browser, $transaction, $transactionCount, 'sponsor');

                // Logout
                $this->logout($browser);
            });
        }, function () use ($fund) {
            $fund->organization
                ->employees()
                ->whereNotIn('identity_address', [$fund->organization->identity_address])
                ->delete();

            $fund && $this->deleteFund($fund);
        });
    }

    /**
     * @param Fund $fund
     * @param int $transactionCont
     * @return VoucherTransaction
     */
    protected function prepareData(Fund $fund, int $transactionCont): VoucherTransaction
    {
        $employees = $this->makeEmployeesAndOffices($fund->organization, $transactionCont);

        $vouchers = array_map(
            fn (Product $product) => $fund->makeProductVoucher($this->makeIdentity(), [], $product->id),
            $this->makeProviderAndProducts($fund, $transactionCont)['approved']
        );

        $this->makeTransactions($vouchers, $employees);

        $transaction = $fund->vouchers()->first()->transactions()->first();
        $this->assertNotNull($transaction);

        // add more transactions to increase list
        $this->makeOtherTransactions($fund, $transaction->provider);

        return $transaction;
    }

    /**
     * @param Fund $fund
     * @param Organization $provider
     * @return void
     */
    protected function makeOtherTransactions(Fund $fund, Organization $provider): void
    {
        // make some transactions for other fund but same organization
        // it will make more items in list on sponsor dashboard
        $otherFund = $this->makeTestFund($fund->organization);

        $vouchers = array_map(
            fn (Product $product) => $otherFund->makeProductVoucher($this->makeIdentity(), [], $product->id),
            $this->makeProductsFundFund(2)
        );

        $this->makeTransactions($vouchers, []);

        // make some transactions for other fund and other organization, but for same provider
        // it will make more items in list on provider dashboard
        $otherOrganization = $this->makeTestOrganization($this->makeIdentity());
        $otherFund2 = $this->makeTestFund($otherOrganization);

        $vouchers = array_map(
            fn (Product $product) => $otherFund2->makeProductVoucher($this->makeIdentity(), [], $product->id),
            $this->makeTestProducts($provider, 2)
        );

        $this->makeTransactions($vouchers, []);
    }

    /**
     * @param array|Voucher[] $vouchers
     * @param Collection|array|Employee[] $employees
     * @return void
     */
    protected function makeTransactions(array $vouchers, Collection|array $employees): void
    {
        foreach ($vouchers as $index => $voucher) {
            $employee = $employees[$index] ?? null;

            $params = [
                'amount' => $voucher->amount,
                'product_id' => $voucher->product_id,
                'employee_id' => $employee?->id,
                'branch_id' => $employee?->office->branch_id,
                'branch_name' => $employee?->office->branch_name,
                'branch_number' => $employee?->office->branch_number,
                'target' => VoucherTransaction::TARGET_PROVIDER,
                'organization_id' => $voucher->product->organization_id,
            ];

            $transaction = $voucher->makeTransaction($params);
            $transaction->notes_provider()->create([
                'message' => $this->faker->sentence,
                'shared' => true,
            ]);

            $transaction->setPaid(null, now());
        }
    }

    /**
     * @param Organization $organization
     * @param int $count
     * @return Collection|array|Employee[]
     */
    protected function makeEmployeesAndOffices(Organization $organization, int $count): \Illuminate\Database\Eloquent\Collection|array
    {
        // clear all not needed employees
        $organization
            ->employees()
            ->whereNotIn('identity_address', [$organization->identity_address])
            ->delete();

        for ($i = 0; $i < $count; $i++) {
            $employee = $organization->addEmployee($this->makeIdentity(), Role::pluck('id')->toArray());

            $office = $this->makeOrganizationOffice($organization, [
                'branch_number' => $this->faker->numberBetween(100000, 1000000),
                'branch_id' => $this->faker->numberBetween(100000, 1000000),
                'branch_name' => $this->faker->name,
            ]);

            $employee->update(['office_id' => $office->id]);
        }

        return $organization
            ->employees()
            ->whereNotIn('identity_address', [$organization->identity_address])
            ->get();
    }

    /**
     * @param Browser $browser
     * @throws TimeoutException
     * @return void
     */
    protected function goToListPage(Browser $browser): void
    {
        $browser->waitFor('@asideMenuGroupFinancial');
        $browser->element('@asideMenuGroupFinancial')->click();
        $browser->waitFor('@transactionsPage');
        $browser->element('@transactionsPage')->click();
    }

    /**
     * @param Browser $browser
     * @param VoucherTransaction $transaction
     * @param int $transactionCount
     * @param string $dashboard
     * @throws TimeOutException
     * @return void
     */
    protected function searchTransaction(
        Browser $browser,
        VoucherTransaction $transaction,
        int $transactionCount,
        string $dashboard
    ): void {
        $browser->waitFor('@searchTransaction');

        // same transactions from one fund so count must be equal to $transactionCount
        $this->searchItem($browser, $transaction, $transaction->voucher->fund->name, $transactionCount);
        $this->searchItem($browser, $transaction, $transaction->branch_id);
        $this->searchItem($browser, $transaction, $transaction->branch_name);
        $this->searchItem($browser, $transaction, $transaction->branch_number);
        $this->searchItem($browser, $transaction, $transaction->product->id);
        $this->searchItem($browser, $transaction, $transaction->product->name);

        // same transactions from one provider so count must be equal to $transactionCount
        if ($dashboard === 'sponsor') {
            $this->searchItem($browser, $transaction, $transaction->product->organization->name, $transactionCount);
        }

        if ($dashboard === 'provider') {
            $this->searchItem($browser, $transaction, $transaction->notes_provider[0]->message);
        }
    }

    /**
     * @param Browser $browser
     * @param VoucherTransaction $transaction
     * @param int|string $query
     * @param int $expectedCount
     * @throws TimeoutException
     * @return void
     */
    protected function searchItem(
        Browser $browser,
        VoucherTransaction $transaction,
        int|string $query,
        int $expectedCount = 1
    ): void {
        $this->clearField($browser, '@searchTransaction');
        $browser->waitUntil("document.querySelectorAll('#transactionsTable tbody tr').length > $expectedCount");

        $browser->type('@searchTransaction', $query);
        $browser->waitUntil("document.querySelectorAll('#transactionsTable tbody tr').length === $expectedCount");
        $browser->waitFor("@transactionItem$transaction->id", 20);
        $browser->assertVisible("@transactionItem$transaction->id");
    }

    /**
     * @param Browser $browser
     * @param string $selector
     * @return void
     */
    protected function clearField(Browser $browser, string $selector): void
    {
        /** @var string $value */
        $value = $browser->value($selector);
        $browser->keys($selector, ...array_fill(0, strlen($value), '{backspace}'));
    }
}
