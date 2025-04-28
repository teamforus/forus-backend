<?php

namespace Tests\Browser;

use App\Models\BankConnection;
use App\Models\Fund;
use App\Models\Implementation;
use App\Models\Organization;
use App\Models\VoucherTransaction;
use App\Models\VoucherTransactionBulk;
use Facebook\WebDriver\Exception\ElementClickInterceptedException;
use Facebook\WebDriver\Exception\NoSuchElementException;
use Facebook\WebDriver\Exception\TimeoutException;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Artisan;
use Laravel\Dusk\Browser;
use Tests\Browser\Traits\HasFrontendActions;
use Tests\Browser\Traits\RollbackModelsTrait;
use Tests\DuskTestCase;
use Tests\Traits\MakesTestBankConnections;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestOrganizations;
use Tests\Traits\MakesVoucherTransaction;
use Throwable;

class VoucherTransactionBulkTest extends DuskTestCase
{
    use WithFaker;
    use MakesTestFunds;
    use HasFrontendActions;
    use RollbackModelsTrait;
    use MakesTestOrganizations;
    use MakesVoucherTransaction;
    use MakesTestBankConnections;

    /**
     * @throws Throwable
     * @return void
     */
    public function testVoucherTransactionBulkCreate(): void
    {
        $implementation = Implementation::byKey('nijmegen');
        $organization = $implementation->organization;
        $fund = $this->makeTestFund($organization);
        $transactionCount = 5;

        $this->makeTransactions($fund, $transactionCount);

        $organization->bank_connection_active()->update([
            'state' => BankConnection::STATE_DISABLED,
        ]);

        $this->rollbackModels([], function () use ($implementation, $organization, $fund, $transactionCount) {
            $this->browse(function (Browser $browser) use ($implementation, $organization, $fund, $transactionCount) {
                $browser->visit($implementation->urlSponsorDashboard());

                // Authorize identity
                $this->loginIdentity($browser, $organization->identity);
                $this->assertIdentityAuthenticatedOnSponsorDashboard($browser, $organization->identity);
                $this->selectDashboardOrganization($browser, $organization);

                $this->goToTransactionsPage($browser, $fund, $transactionCount);

                // assert missing "make bulk" button because organization doesn't have bank connections
                $browser->assertMissing('@bulkPendingNow');

                // make bank connection and create bulk
                $connection = $this->makeBankConnection($organization);
                $browser->refresh();
                $this->makeBulk($browser);

                $bulk = VoucherTransactionBulk::where('bank_connection_id', $connection->id)->first();
                $this->assertNotNull($bulk);

                $this->goToTransactionBulksPage($browser);
                $browser->waitFor("@transactionBulkRow$bulk->id");

                // Logout
                $this->logout($browser);
            });
        }, function () use ($organization, $fund) {
            $this->deleteFund($fund);
            $this->clearBankConnectionAndBulks($organization);
        });
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testVoucherTransactionBulkCreateByCommand(): void
    {
        $implementation = Implementation::byKey('nijmegen');
        $organization = $implementation->organization;
        $fund = $this->makeTestFund($organization);
        $this->makeTransactions($fund);

        $organization->bank_connection_active()->update([
            'state' => BankConnection::STATE_DISABLED,
        ]);

        $this->rollbackModels([
            [$organization, $organization->only(['bank_cron_time'])],
        ], function () use ($implementation, $organization) {
            $this->browse(function (Browser $browser) use ($implementation, $organization) {
                $browser->visit($implementation->urlSponsorDashboard());

                // Authorize identity
                $this->loginIdentity($browser, $organization->identity);
                $this->assertIdentityAuthenticatedOnSponsorDashboard($browser, $organization->identity);
                $this->selectDashboardOrganization($browser, $organization);

                $connection = $this->makeBankConnection($organization);
                $organization->forceFill(['bank_cron_time' => now()])->save();

                Artisan::call('bank:bulks-build');

                $bulk = VoucherTransactionBulk::where('bank_connection_id', $connection->id)->first();
                $this->assertNotNull($bulk);

                $this->goToTransactionBulksPage($browser);
                $browser->waitFor("@transactionBulkRow$bulk->id");

                // Logout
                $this->logout($browser);
            });
        }, function () use ($organization, $fund) {
            $this->deleteFund($fund);
            $this->clearBankConnectionAndBulks($organization);
        });
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testVoucherTransactionMeta(): void
    {
        $implementation = Implementation::byKey('nijmegen');
        $organization = $implementation->organization;
        $fund = $this->makeTestFund($organization);
        $transactions = $this->makeTransactions($fund);

        $organization->bank_connection_active()->update([
            'state' => BankConnection::STATE_DISABLED,
        ]);

        $this->makeBankConnection($organization);

        $this->rollbackModels([], function () use ($implementation, $organization, $transactions, $fund) {
            $this->browse(function (Browser $browser) use ($implementation, $organization, $transactions, $fund) {
                $browser->visit($implementation->urlSponsorDashboard());

                // Authorize identity
                $this->loginIdentity($browser, $organization->identity);
                $this->assertIdentityAuthenticatedOnSponsorDashboard($browser, $organization->identity);
                $this->selectDashboardOrganization($browser, $organization);

                $this->goToTransactionsPage($browser, $fund, $transactions->count());

                $browser->waitFor('@pendingBulkingMetaText');
                $browser->assertSeeIn('@pendingBulkingMetaText', $transactions->count());
                $browser->assertSeeIn('@pendingBulkingMetaText', currency_format_locale($transactions->sum('amount')));

                // Logout
                $this->logout($browser);
            });
        }, function () use ($organization, $fund) {
            $this->deleteFund($fund);
            $this->clearBankConnectionAndBulks($organization);
        });
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testVoucherTransactionExportSepaAndSetAccepted(): void
    {
        $implementation = Implementation::byKey('nijmegen');
        $organization = $implementation->organization;
        $fund = $this->makeTestFund($organization);
        $transactionCount = 5;
        $this->makeTransactions($fund, $transactionCount);

        $organization->bank_connection_active()->update([
            'state' => BankConnection::STATE_DISABLED,
        ]);

        $connection = $this->makeBankConnection($organization);

        $this->rollbackModels([
            [$organization, $organization->only(['allow_manual_bulk_processing'])],
        ], function () use ($implementation, $organization, $connection, $fund, $transactionCount) {
            $this->browse(function (Browser $browser) use ($implementation, $organization, $connection, $fund, $transactionCount) {
                $organization->forceFill([
                    'allow_manual_bulk_processing' => true,
                ])->save();

                $browser->visit($implementation->urlSponsorDashboard());

                // Authorize identity
                $this->loginIdentity($browser, $organization->identity);
                $this->assertIdentityAuthenticatedOnSponsorDashboard($browser, $organization->identity);
                $this->selectDashboardOrganization($browser, $organization);

                $this->goToTransactionsPage($browser, $fund, $transactionCount);
                $this->makeBulk($browser);

                $bulk = VoucherTransactionBulk::where('bank_connection_id', $connection->id)->first();
                $this->assertNotNull($bulk);
                $this->goToTransactionBulksPage($browser);
                $browser->waitFor("@transactionBulkRow$bulk->id");
                $browser->click("@transactionBulkRow$bulk->id");

                $this->exportSepa($browser);
                $this->assertTrue($bulk->refresh()->is_exported);

                $this->acceptManually($browser);
                $this->assertTrue($bulk->fresh()->accepted_manually);
                $logs = $bulk->logs()->where('event', VoucherTransactionBulk::EVENT_ACCEPTED_MANUALLY)->get();

                $this->assertEquals(1, $logs->count(), 'Event accepted manually must be created');
                $this->assertEquals($organization->employees[0]->id, $logs[0]->data['employee_id']);

                // Logout
                $this->logout($browser);
            });
        }, function () use ($organization, $fund) {
            $this->deleteFund($fund);
            $this->clearBankConnectionAndBulks($organization);
        });
    }

    /**
     * @param Browser $browser
     * @param Fund $fund
     * @param int $expectedCount
     * @throws ElementClickInterceptedException
     * @throws NoSuchElementException
     * @throws TimeoutException
     * @return void
     */
    protected function goToTransactionsPage(Browser $browser, Fund $fund, int $expectedCount): void
    {
        $browser->waitFor('@asideMenuGroupFinancial');
        $browser->element('@asideMenuGroupFinancial')->click();
        $browser->waitFor('@transactionsPage');
        $browser->element('@transactionsPage')->click();
        $browser->waitFor('[data-dusk^="transactionItem"]');

        // filter by fund
        $browser->waitFor('@showFilters');
        $browser->click('@showFilters');
        $browser->waitFor('@fundSelectToggle');
        $browser->click('@fundSelectToggle');
        $browser->waitFor('@fundSelect');

        $browser->click('@fundSelect .select-control-search');
        $this->findOptionElement($browser, '@fundSelect', $fund->name)->click();
        $browser->click('@hideFilters');

        $browser->waitUsing(10, 100, function () use ($browser, $expectedCount) {
            return count($browser->elements('[data-dusk^="transactionItem"]')) === $expectedCount;
        });
    }

    /**
     * @param Browser $browser
     * @throws TimeoutException
     * @return void
     */
    protected function goToTransactionBulksPage(Browser $browser): void
    {
        $browser->waitFor('@asideMenuGroupFinancial');
        $browser->element('@asideMenuGroupFinancial')->click();
        $browser->waitFor('@transactionsPage');
        $browser->element('@transactionsPage')->click();
        $browser->waitFor('@transaction_view_bulks');
        $browser->element('@transaction_view_bulks')->click();
        $browser->waitFor('[data-dusk^="transactionBulkRow"]');
    }

    /**
     * @param Browser $browser
     * @throws TimeoutException
     * @throws \Facebook\WebDriver\Exception\ElementClickInterceptedException
     * @throws \Facebook\WebDriver\Exception\NoSuchElementException
     * @return void
     */
    protected function makeBulk(Browser $browser): void
    {
        $browser->waitFor('@bulkPendingNow');
        $browser->click('@bulkPendingNow');
        $browser->waitFor('@btnDangerZoneSubmit');
        $browser->click('@btnDangerZoneSubmit');
        $browser->waitUntilMissing('@bulkPendingNow');

        $this->assertAndCloseSuccessNotification($browser);
    }

    /**
     * @param Browser $browser
     * @throws TimeoutException
     * @throws \Facebook\WebDriver\Exception\ElementClickInterceptedException
     * @throws \Facebook\WebDriver\Exception\NoSuchElementException
     * @return void
     */
    protected function exportSepa(Browser $browser): void
    {
        $browser->waitFor('@exportSepaBtn');
        $browser->click('@exportSepaBtn');
        $browser->waitFor('@btnDangerZoneSubmit');
        $browser->click('@btnDangerZoneSubmit');

        $this->assertAndCloseSuccessNotification($browser);
    }

    /**
     * @param Browser $browser
     * @throws TimeoutException
     * @throws \Facebook\WebDriver\Exception\ElementClickInterceptedException
     * @throws \Facebook\WebDriver\Exception\NoSuchElementException
     * @return void
     */
    protected function acceptManually(Browser $browser): void
    {
        $browser->waitFor('@acceptManuallyBtn');
        $browser->click('@acceptManuallyBtn');
        $browser->waitFor('@btnDangerZoneSubmit');
        $browser->click('@btnDangerZoneSubmit');

        $this->assertAndCloseSuccessNotification($browser);
    }

    /**
     * @param Organization $organization
     * @return void
     */
    protected function clearBankConnectionAndBulks(Organization $organization): void
    {
        $organization->bank_connection_active?->voucher_transaction_bulks->each(function (VoucherTransactionBulk $bulk) {
            VoucherTransaction::query()
                ->where('voucher_transaction_bulk_id', $bulk->id)
                ->update(['voucher_transaction_bulk_id' => null]);

            $bulk->delete();
        });

        $organization->bank_connection_active()->delete();
    }
}
