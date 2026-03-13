<?php

namespace Tests\Browser\Filters\Dashboards;

use App\Models\Fund;
use App\Models\Implementation;
use App\Models\Voucher;
use Facebook\WebDriver\Exception\TimeoutException;
use Laravel\Dusk\Browser;
use Tests\Browser\Traits\HasFrontendActions;
use Tests\Browser\Traits\NavigatesFrontendDashboard;
use Tests\Browser\Traits\RollbackModelsTrait;
use Tests\DuskTestCase;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestVouchers;
use Throwable;

class ImplementationNotificationSendIdentitiesSearchFilterTest extends DuskTestCase
{
    use MakesTestFunds;
    use MakesTestVouchers;
    use HasFrontendActions;
    use RollbackModelsTrait;
    use NavigatesFrontendDashboard;

    /**
     * @throws Throwable
     * @return void
     */
    public function testImplementationNotificationSendIdentitiesSearchFilter(): void
    {
        $implementation = Implementation::byKey('nijmegen');
        $fund = $this->makeTestFund($implementation->organization, implementation: $implementation);

        $withEmailsCount = 7;
        $withoutEmailsCount = 3;
        $emptyBalanceCount = 5;

        // add + 2 to emptyBalance for topup that 2 empty vouchers, so they must have balance > 0
        $this->prepareTestingData(
            $fund,
            withEmailCount: $withEmailsCount,
            withoutEmailCount: $withoutEmailsCount,
            emptyBalance: $emptyBalanceCount + 2,
            topUpEmptyVouchers: 2
        );

        $this->rollbackModels([], function () use (
            $implementation,
            $fund,
            $withEmailsCount,
            $withoutEmailsCount,
            $emptyBalanceCount
        ) {
            $this->browse(function (Browser $browser) use (
                $implementation,
                $fund,
                $withEmailsCount,
                $withoutEmailsCount,
                $emptyBalanceCount
            ) {
                $organization = $implementation->organization;
                $browser->visit($implementation->urlSponsorDashboard());

                // Authorize identity
                $this->loginIdentity($browser, $organization->identity);
                $this->assertIdentityAuthenticatedOnSponsorDashboard($browser, $organization->identity);
                $this->selectDashboardOrganization($browser, $organization);

                $this->goToNotificationsPage($browser);

                $browser->waitFor('@notificationSendBtn');
                $browser->element('@notificationSendBtn')->click();

                $browser->waitFor('@showIdentitiesBtn');
                $browser->click('@showIdentitiesBtn');

                $this->changeSelectControl($browser, '@selectControlFunds', text: $fund->name);

                // filter by has active vouchers
                $this->changeSelectControl($browser, '@selectControlIdentityTargets', text: 'Alle gebruikers met een actief tegoed');

                $this->assertIdentitiesCounts(
                    $browser,
                    activeCount: $withEmailsCount + $withoutEmailsCount,
                    selectedCount: $withEmailsCount,
                    excludedCount: 0,
                    withEmailsCount: $withoutEmailsCount
                );

                // change filter by has balance
                $this->changeSelectControl($browser, '@selectControlIdentityTargets', text: 'Alle gebruikers met nog beschikbaar resterend tegoed');

                $this->assertIdentitiesCounts(
                    $browser,
                    activeCount: $withEmailsCount + $withoutEmailsCount,
                    selectedCount: $withEmailsCount - $emptyBalanceCount,
                    excludedCount: $emptyBalanceCount,
                    withEmailsCount: $withoutEmailsCount
                );

                // Logout
                $this->logout($browser);
            });
        }, function () use ($fund) {
            $fund->organization->employees()->where('identity_address', '!=', $fund->organization->identity_address)->delete();
            $fund && $this->deleteFund($fund);
        });
    }

    /**
     * @param Fund $fund
     * @param int $withEmailCount
     * @param int $withoutEmailCount
     * @param int $emptyBalance
     * @param int $topUpEmptyVouchers
     * @param int $deactivatedVouchers
     * @throws Throwable
     * @return Voucher[]
     */
    private function prepareTestingData(
        Fund $fund,
        int $withEmailCount,
        int $withoutEmailCount,
        int $emptyBalance,
        int $topUpEmptyVouchers = 0,
        int $deactivatedVouchers = 3
    ): array {
        $amount = 100;
        $employee = $fund->organization->employees()->first();

        /** @var Voucher[] $vouchers */
        $vouchers = [];

        for ($i = 0; $i < $withEmailCount; $i++) {
            $vouchers[] = $fund->makeVoucher($this->makeIdentity($this->makeUniqueEmail()), amount: $amount);
        }

        for ($i = 0; $i < $withoutEmailCount; $i++) {
            $vouchers[] = $fund->makeVoucher($this->makeIdentity(), amount: $amount)->makeTransaction();
        }

        for ($i = 0; $i < $emptyBalance; $i++) {
            $vouchers[$i]->makeDirectPayment($this->faker->iban(), $this->faker->name(), $employee);

            if ($i < $topUpEmptyVouchers) {
                $vouchers[$i]->makeSponsorTopUpTransaction($employee, $amount);
            }
        }

        for ($i = 0; $i < $deactivatedVouchers; $i++) {
            $fund->makeVoucher($this->makeIdentity($this->makeUniqueEmail()), amount: $amount)->deactivate();
        }

        return $vouchers;
    }

    /**
     * @param Browser $browser
     * @param int $activeCount
     * @param int $selectedCount
     * @param int $excludedCount
     * @param int $withEmailsCount
     * @throws TimeoutException
     * @return void
     */
    private function assertIdentitiesCounts(
        Browser $browser,
        int $activeCount,
        int $selectedCount,
        int $excludedCount,
        int $withEmailsCount,
    ): void {
        $this->assertRowsCount($browser, $selectedCount, '@tableIdentityContent');

        // assert counts
        $browser->waitFor('@identityCountActive');
        $browser->assertSeeIn('@identityCountActive', $activeCount);

        $browser->waitFor('@identityCountSelected');
        $browser->assertSeeIn('@identityCountSelected', $selectedCount);

        $browser->waitFor('@identityCountExcluded');
        $browser->assertSeeIn('@identityCountExcluded', $excludedCount);

        $browser->waitFor('@identityCountWithoutEmail');
        $browser->assertSeeIn('@identityCountWithoutEmail', $withEmailsCount);
    }
}
