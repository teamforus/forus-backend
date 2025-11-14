<?php

namespace Browser;

use App\Mail\Funds\ProviderStateUnsubscribedMail;
use App\Models\FundProvider;
use App\Services\MailDatabaseLoggerService\Traits\AssertsSentEmails;
use Carbon\Carbon;
use Facebook\WebDriver\Exception\ElementClickInterceptedException;
use Facebook\WebDriver\Exception\NoSuchElementException;
use Facebook\WebDriver\Exception\TimeOutException;
use Laravel\Dusk\Browser;
use Tests\Browser\Traits\HasFrontendActions;
use Tests\Browser\Traits\NavigatesFrontendDashboard;
use Tests\Browser\Traits\RollbackModelsTrait;
use Tests\DuskTestCase;
use Tests\Traits\MakesProductReservations;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestIdentities;
use Tests\Traits\MakesTestOrganizationOffices;
use Throwable;

class ProviderFundsUnsubscribeTest extends DuskTestCase
{
    use MakesTestFunds;
    use AssertsSentEmails;
    use HasFrontendActions;
    use MakesTestIdentities;
    use RollbackModelsTrait;
    use MakesProductReservations;
    use NavigatesFrontendDashboard;
    use MakesTestOrganizationOffices;

    /**
     * @throws Throwable
     * @return void
     */
    public function testFundUnsubscribeInProviderDashboard(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity($this->makeUniqueEmail()));
        $implementation = $this->makeTestImplementation($organization);
        $fund = $this->makeTestFund($organization);

        $identity = $this->makeIdentity($this->makeUniqueEmail());
        $provider = $this->makeTestProviderOrganization($identity);
        $fundProvider = $this->makeTestFundProvider($provider, $fund);
        $this->makeOrganizationOffice($provider);

        $this->rollbackModels([], function () use ($implementation, $identity, $provider, $fundProvider) {
            $this->browse(function (Browser $browser) use ($implementation, $identity, $provider, $fundProvider) {
                $startDate = now();
                $browser->visit($implementation->urlProviderDashboard());

                // Authorize identity
                $this->loginIdentity($browser, $identity);
                $this->assertIdentityAuthenticatedOnProviderDashboard($browser, $identity);
                $this->selectDashboardOrganization($browser, $provider);

                // got to active funds and unsubscribe
                $this->goToProviderFundsPage($browser, 'funds_active');
                $this->assertFundVisibility($browser, $fundProvider, '@activeTableFunds', available: true);

                $note = $this->faker->sentence;
                $this->unsubscribe($browser, $fundProvider, $note);
                $this->assertEmailSent($fundProvider, $startDate);
                $this->assertLogCreated($fundProvider, $note);

                $this->assertFundVisibility($browser, $fundProvider, '@activeTableFunds', available: false);

                // reapply to fund
                $this->goToProviderFundsPage($browser, 'funds_unsubscribed');
                $this->assertFundVisibility($browser, $fundProvider, '@unsubscribedTableFunds', available: true);
                $this->reApply($browser, $fundProvider);
                $this->assertFundVisibility($browser, $fundProvider, '@unsubscribedTableFunds', available: false);

                // assert fund provider in the pending tab now
                $this->goToProviderFundsPage($browser, 'funds_pending');
                $this->assertFundVisibility($browser, $fundProvider, '@pending_rejectedTableFunds', available: true);

                // Logout
                $this->logout($browser);
            });
        }, function () use ($fund) {
            $fund && $this->deleteFund($fund);
        });
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testFundUnsubscribeForbiddenByReservation(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity($this->makeUniqueEmail()));
        $implementation = $this->makeTestImplementation($organization);
        $fund = $this->makeTestFund($organization);

        $identity = $this->makeIdentity($this->makeUniqueEmail());
        $provider = $this->makeTestProviderOrganization($identity);
        $fundProvider = $this->makeTestFundProvider($provider, $fund);
        $this->makeOrganizationOffice($provider);

        $voucher = $this->makeTestVoucher($fund, identity: $this->makeIdentity($this->makeUniqueEmail()));
        $reservation = $this->makeReservation($voucher, $this->makeTestProductForReservation($provider));

        $this->rollbackModels([], function () use (
            $implementation,
            $identity,
            $provider,
            $fundProvider,
            $reservation
        ) {
            $this->browse(function (Browser $browser) use (
                $implementation,
                $identity,
                $provider,
                $fundProvider,
                $reservation
            ) {
                $startDate = now();
                $browser->visit($implementation->urlProviderDashboard());

                // Authorize identity
                $this->loginIdentity($browser, $identity);
                $this->assertIdentityAuthenticatedOnProviderDashboard($browser, $identity);
                $this->selectDashboardOrganization($browser, $provider);

                // got to active funds
                $this->goToProviderFundsPage($browser, 'funds_active');
                $this->assertFundVisibility($browser, $fundProvider, '@activeTableFunds', available: true);

                // assert provider can not unsubscribe if there are not resolved reservations
                $note = $this->faker->sentence;
                $this->unsubscribe($browser, $fundProvider, $note, false);

                // resolve reservations and accept provider can unsubscribe
                $reservation->acceptProvider();
                $this->unsubscribe($browser, $fundProvider, $note);
                $this->assertEmailSent($fundProvider, $startDate);
                $this->assertLogCreated($fundProvider, $note);

                $this->assertFundVisibility($browser, $fundProvider, '@activeTableFunds', available: false);

                // Logout
                $this->logout($browser);
            });
        }, function () use ($fund) {
            $fund && $this->deleteFund($fund);
        });
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testFundUnsubscribeForbiddenByVoucher(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity($this->makeUniqueEmail()));
        $implementation = $this->makeTestImplementation($organization);
        $fund = $this->makeTestFund($organization);

        $identity = $this->makeIdentity($this->makeUniqueEmail());
        $provider = $this->makeTestProviderOrganization($identity);
        $fundProvider = $this->makeTestFundProvider($provider, $fund);
        $this->makeOrganizationOffice($provider);

        $product = $this->makeTestProduct($provider);
        $voucher = $this->makeTestProductVoucher($fund, identity: $this->makeIdentity($this->makeUniqueEmail()), productId: $product->id);
        $voucher->setPending();

        $this->rollbackModels([], function () use (
            $implementation,
            $identity,
            $provider,
            $fundProvider,
            $voucher
        ) {
            $this->browse(function (Browser $browser) use (
                $implementation,
                $identity,
                $provider,
                $fundProvider,
                $voucher
            ) {
                $startDate = now();
                $browser->visit($implementation->urlProviderDashboard());

                // Authorize identity
                $this->loginIdentity($browser, $identity);
                $this->assertIdentityAuthenticatedOnProviderDashboard($browser, $identity);
                $this->selectDashboardOrganization($browser, $provider);

                // got to active funds
                $this->goToProviderFundsPage($browser, 'funds_active');
                $this->assertFundVisibility($browser, $fundProvider, '@activeTableFunds', available: true);

                // assert provider can not unsubscribe if there are not resolved product vouchers
                $note = $this->faker->sentence;
                $this->unsubscribe($browser, $fundProvider, $note, false);

                // resolve product voucher and accept provider can unsubscribe
                $voucher->activateAsSponsor('test', $implementation->organization->findEmployee($implementation->organization->identity));
                $this->unsubscribe($browser, $fundProvider, $note);
                $this->assertEmailSent($fundProvider, $startDate);
                $this->assertLogCreated($fundProvider, $note);

                $this->assertFundVisibility($browser, $fundProvider, '@activeTableFunds', available: false);

                // Logout
                $this->logout($browser);
            });
        }, function () use ($fund) {
            $fund && $this->deleteFund($fund);
        });
    }

    /**
     * @param Browser $browser
     * @param FundProvider $fundProvider
     * @param string $selector
     * @param bool $available
     * @throws TimeoutException
     * @return void
     */
    protected function assertFundVisibility(Browser $browser, FundProvider $fundProvider, string $selector, bool $available): void
    {
        $this->searchTable(
            $browser,
            selector: $selector,
            value: $fundProvider->fund->name,
            id: $available ? $fundProvider->id : null,
            expected: $available ? 1 : 0,
        );
    }

    /**
     * @param Browser $browser
     * @param FundProvider $fundProvider
     * @param string $note
     * @param bool $assertSuccess
     * @throws ElementClickInterceptedException
     * @throws NoSuchElementException
     * @throws TimeoutException
     * @return void
     */
    private function unsubscribe(
        Browser $browser,
        FundProvider $fundProvider,
        string $note,
        bool $assertSuccess = true
    ): void {
        $browser->waitFor("@activeTableFundsRow$fundProvider->id");
        $browser->within("@activeTableFundsRow$fundProvider->id", fn (Browser $b) => $b->press('@btnFundProviderMenu'));
        $browser->waitFor("@btnUnsubscribe$fundProvider->id");
        $browser->press("@btnUnsubscribe$fundProvider->id");

        $browser->waitFor('@modalFundUnsubscribe');

        $browser->within('@modalFundUnsubscribe', function (Browser $browser) use ($note) {
            $browser->waitFor('@noteInput');
            $browser->type('@noteInput', $note);
            $browser->press('@submitBtn');
        });

        if ($assertSuccess) {
            $browser->waitUntilMissing('@modalFundUnsubscribe');
            $this->assertAndCloseSuccessNotification($browser);
        } else {
            $browser->waitFor('@modalFundUnsubscribeError');
            $browser->click('@closeBtn');
            $browser->waitUntilMissing('@modalFundUnsubscribe');
        }
    }

    /**
     * @param Browser $browser
     * @param FundProvider $fundProvider
     * @throws TimeoutException
     * @return void
     */
    private function reApply(Browser $browser, FundProvider $fundProvider): void
    {
        $browser->waitFor("@unsubscribedTableFundsRow$fundProvider->id");
        $browser->within("@unsubscribedTableFundsRow$fundProvider->id", fn (Browser $b) => $b->press('@btnFundProviderMenu'));
        $browser->waitFor("@btnApplyFund$fundProvider->id");
        $browser->press("@btnApplyFund$fundProvider->id");

        $browser->waitFor('@modalNotification');

        $browser->within('@modalNotification', function (Browser $browser) {
            $browser->waitFor('@submitBtn');
            $browser->press('@submitBtn');
        });

        $browser->waitUntilMissing('@modalNotification');
    }

    /**
     * @param FundProvider $fundProvider
     * @param Carbon $startDate
     * @return void
     */
    private function assertEmailSent(FundProvider $fundProvider, Carbon $startDate): void
    {
        $this->assertMailableSent($fundProvider->fund->organization->identity->email, ProviderStateUnsubscribedMail::class, $startDate);
    }

    /**
     * @param FundProvider $fundProvider
     * @param string $note
     * @return void
     */
    private function assertLogCreated(FundProvider $fundProvider, string $note): void
    {
        $logs = $fundProvider->logs()->where('event', $fundProvider::EVENT_STATE_UNSUBSCRIBED)->get();

        $this->assertEquals(1, $logs->count(), 'Event state unsubscribed must be created');
        $this->assertEquals($note, $logs[0]->data['note']);
    }
}
