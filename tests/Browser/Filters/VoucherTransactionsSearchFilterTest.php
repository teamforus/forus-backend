<?php

namespace Tests\Browser\Filters;

use App\Models\Employee;
use App\Models\Fund;
use App\Models\Implementation;
use App\Models\Organization;
use App\Models\Product;
use App\Models\Role;
use App\Models\Voucher;
use App\Models\VoucherTransaction;
use App\Searches\VoucherTransactionsSearch;
use Facebook\WebDriver\Exception\TimeOutException;
use Illuminate\Database\Eloquent\Collection;
use Laravel\Dusk\Browser;
use Tests\Browser\Traits\HasFrontendActions;
use Tests\Browser\Traits\RollbackModelsTrait;
use Tests\DuskTestCase;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestOrganizationOffices;
use Tests\Traits\MakesTestVouchers;
use Throwable;

class VoucherTransactionsSearchFilterTest extends DuskTestCase
{
    use MakesTestFunds;
    use MakesTestVouchers;
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
        $transaction = $this->prepareTestingData($fund, $transactionCount);
        $this->removePossibleDuplicateByIds($transaction, 'provider');

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

                // Go to transactions page and assert search is working
                $this->goToTransactionsPage($browser);
                $this->assertTransactionsSearchIsWorking($browser, $transaction, $transactionCount, 'provider');

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
        $transaction = $this->prepareTestingData($fund, $transactionCount);
        $this->removePossibleDuplicateByIds($transaction);

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
                $this->goToTransactionsPage($browser);
                $this->assertTransactionsSearchIsWorking($browser, $transaction, $transactionCount, 'sponsor');

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
     * @param int $transactionsCount
     * @return VoucherTransaction
     */
    protected function prepareTestingData(Fund $fund, int $transactionsCount): VoucherTransaction
    {
        $employees = $this->makeEmployeesAndOffices($fund->organization, $transactionsCount);

        $vouchers = array_map(
            fn (Product $product) => $this->makeTestProductVoucher($fund, $this->makeIdentity(), [], $product->id),
            $this->makeProviderAndProducts($fund, $transactionsCount)['approved']
        );

        foreach ($vouchers as $index => $voucher) {
            $this->makeTransactionWithProviderNotes($voucher, $employees[$index]);
        }

        $transaction = $fund->vouchers()->first()->transactions()->first();
        $this->assertNotNull($transaction);

        // add more transactions to increase the number of items on the dashboard transactions page
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
        // make two vouchers on a new fund under the same organization with provider notes for the sponsor dashboard
        $otherFund = $this->makeTestFund($fund->organization);

        $vouchers = array_map(
            fn (Product $product) => $this->makeTestProductVoucher($otherFund, $this->makeIdentity(), [], $product->id),
            $this->makeProductsFundFund(2)
        );

        array_map(fn ($voucher) => $this->makeTransactionWithProviderNotes($voucher), $vouchers);

        // make two vouchers on a new fund under a different organization with provider notes for the provider dashboard
        $otherOrganization = $this->makeTestOrganization($this->makeIdentity());
        $otherFund2 = $this->makeTestFund($otherOrganization);

        $vouchers = array_map(
            fn (Product $product) => $this->makeTestProductVoucher($otherFund2, $this->makeIdentity(), [], $product->id),
            $this->makeTestProducts($provider, 2)
        );

        array_map(fn ($voucher) => $this->makeTransactionWithProviderNotes($voucher), $vouchers);
    }

    /**
     * @param Voucher $voucher
     * @param Employee|null $employee
     * @return void
     */
    protected function makeTransactionWithProviderNotes(Voucher $voucher, ?Employee $employee = null): void
    {
        $transaction = $voucher->makeTransaction([
            'amount' => $voucher->amount,
            'product_id' => $voucher->product_id,
            'employee_id' => $employee?->id,
            'branch_id' => $employee?->office->branch_id,
            'branch_name' => $employee?->office->branch_name,
            'branch_number' => $employee?->office->branch_number,
            'target' => VoucherTransaction::TARGET_PROVIDER,
            'organization_id' => $voucher->product->organization_id,
        ]);

        $transaction->notes_provider()->create([
            'message' => $this->faker->sentence,
            'shared' => true,
        ]);

        $transaction->setPaid(null, now());
    }

    /**
     * @param Organization $organization
     * @param int $count
     * @return Collection|array|Employee[]
     */
    protected function makeEmployeesAndOffices(Organization $organization, int $count): Collection|array
    {
        // clear all not needed employees
        $organization
            ->employees()
            ->whereNotIn('identity_address', [$organization->identity_address])
            ->delete();

        for ($i = 0; $i < $count; $i++) {
            $employee = $organization->addEmployee($this->makeIdentity(), Role::pluck('id')->toArray());

            $office = $this->makeOrganizationOffice($organization, [
                'branch_id' => $this->faker->numberBetween(100000, 1000000),
                'branch_name' => $this->faker->name,
                'branch_number' => $this->faker->numberBetween(100000, 1000000),
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
    protected function goToTransactionsPage(Browser $browser): void
    {
        $browser->waitFor('@asideMenuGroupFinancial');
        $browser->element('@asideMenuGroupFinancial')->click();
        $browser->waitFor('@transactionsPage');
        $browser->element('@transactionsPage')->click();
    }

    /**
     * @param Browser $browser
     * @param VoucherTransaction $transaction
     * @param int $count
     * @param string $dashboard
     *@throws TimeOutException
     * @return void
     */
    protected function assertTransactionsSearchIsWorking(
        Browser $browser,
        VoucherTransaction $transaction,
        int $count,
        string $dashboard
    ): void {
        // searching by fund name should result in all transactions from the target fund being shown
        $this->searchTable($browser, '@tableTransaction', $transaction->voucher->fund->name, $transaction->id, $count);
        $this->searchTable($browser, '@tableTransaction', '###############', null, 0);

        // unique transaction values should result in a single transaction being shown
        $this->searchTable($browser, '@tableTransaction', $transaction->branch_id, $transaction->id);
        $this->searchTable($browser, '@tableTransaction', '###############', null, 0);
        $this->searchTable($browser, '@tableTransaction', $transaction->branch_name, $transaction->id);
        $this->searchTable($browser, '@tableTransaction', '###############', null, 0);
        $this->searchTable($browser, '@tableTransaction', $transaction->branch_number, $transaction->id);
        $this->searchTable($browser, '@tableTransaction', '###############', null, 0);
        $this->searchTable($browser, '@tableTransaction', $transaction->product->name, $transaction->id);
        $this->searchTable($browser, '@tableTransaction', '###############', null, 0);

        // searching by provider name should show only the transactions from the target provider
        if ($dashboard === 'sponsor') {
            $this->searchTable($browser, '@tableTransaction', $transaction->product->organization->name, $transaction->id, $count);
            $this->searchTable($browser, '@tableTransaction', '###############', null, 0);
        }

        // searching by provider note should only show the target transaction (give the note is unique)
        if ($dashboard === 'provider') {
            $this->searchTable($browser, '@tableTransaction', $transaction->notes_provider[0]->message, $transaction->id);
            $this->searchTable($browser, '@tableTransaction', '###############', null, 0);
        }
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

    /**
     * @param VoucherTransaction $transaction
     * @param string $as
     * @return void
     */
    protected function removePossibleDuplicateByIds(VoucherTransaction $transaction, string $as = 'sponsor'): void
    {
        $builder = new VoucherTransactionsSearch([], VoucherTransaction::query());

        $builder = match ($as) {
            'sponsor' => $builder->searchSponsor($transaction->voucher->fund->organization),
            'provider' => $builder->searchProvider(),
        };

        $builder->where('id', $transaction->product->id)->first()?->delete();
    }
}
