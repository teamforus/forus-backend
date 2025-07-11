<?php

namespace Tests\Browser\Filters\Webshop;

use App\Models\Fund;
use App\Models\Implementation;
use App\Models\Reimbursement;
use Facebook\WebDriver\Exception\ElementClickInterceptedException;
use Facebook\WebDriver\Exception\NoSuchElementException;
use Facebook\WebDriver\Exception\TimeOutException;
use Laravel\Dusk\Browser;
use Tests\Browser\Traits\HasFrontendActions;
use Tests\Browser\Traits\RollbackModelsTrait;
use Tests\DuskTestCase;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestReimbursements;
use Throwable;

class ReimbursementsSearchFilterTest extends DuskTestCase
{
    use MakesTestFunds;
    use HasFrontendActions;
    use RollbackModelsTrait;
    use MakesTestReimbursements;

    /**
     * @throws Throwable
     * @return void
     */
    public function testReimbursementsFilter(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity($this->makeUniqueEmail()));
        $implementation = Implementation::byKey('nijmegen');

        $identity = $this->makeIdentity($this->makeUniqueEmail());

        $voucher1 = $this->makeTestFund($organization, fundConfigsData: [
            'allow_reimbursements' => true,
            'implementation_id' => $implementation->id,
        ])->makeVoucher($identity);

        $voucher2 = $this->makeTestFund($organization, fundConfigsData: [
            'allow_reimbursements' => true,
            'implementation_id' => $implementation->id,
        ])->makeVoucher($identity);

        $reimbursement = $this->makeReimbursement($voucher1, true);
        $reimbursement2 = $this->makeReimbursement($voucher2, true);

        $this->rollbackModels([], function () use ($implementation, $identity, $reimbursement, $reimbursement2) {
            $this->browse(function (Browser $browser) use ($implementation, $identity, $reimbursement, $reimbursement2) {
                $browser->visit($implementation->urlWebshop());

                $this->loginIdentity($browser, $identity);
                $this->assertIdentityAuthenticatedOnWebshop($browser, $identity);
                $this->goToIdentityReimbursements($browser);

                $this->assertReimbursementsFilterByFund($browser, $reimbursement, $reimbursement2);
                $this->assertReimbursementsFilterByFund($browser, $reimbursement2, $reimbursement);

                $this->logout($browser);
            });
        }, function () use ($organization) {
            $organization->funds->each(fn (Fund $fund) => $this->deleteFund($fund));
        });
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testReimbursementsFilterByStateTabs(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity($this->makeUniqueEmail()));
        $implementation = Implementation::byKey('nijmegen');
        $employee = $organization->findEmployee($organization->identity);
        $identity = $this->makeIdentity($this->makeUniqueEmail());

        $fundConfig = [
            'allow_reimbursements' => true,
            'implementation_id' => $implementation->id,
        ];

        $voucher1 = $this->makeTestFund($organization, fundConfigsData: $fundConfig)->makeVoucher($identity);
        $voucher2 = $this->makeTestFund($organization, fundConfigsData: $fundConfig)->makeVoucher($identity);
        $voucher3 = $this->makeTestFund($organization, fundConfigsData: $fundConfig)->makeVoucher($identity);
        $voucher4 = $this->makeTestFund($organization, fundConfigsData: $fundConfig)->makeVoucher($identity);

        $pendingReimbursement = $this->makeReimbursement($voucher1, true);
        $approvedReimbursement = $this->makeReimbursement($voucher2, true)->assign($employee)->approve();
        $declinedReimbursement = $this->makeReimbursement($voucher3, true)->assign($employee)->decline();
        $draftReimbursement = $this->makeReimbursement($voucher4, false);

        $this->rollbackModels([], function () use (
            $implementation,
            $identity,
            $pendingReimbursement,
            $approvedReimbursement,
            $declinedReimbursement,
            $draftReimbursement,
        ) {
            $this->browse(function (Browser $browser) use (
                $implementation,
                $identity,
                $pendingReimbursement,
                $approvedReimbursement,
                $declinedReimbursement,
                $draftReimbursement,
            ) {
                $browser->visit($implementation->urlWebshop());

                $this->loginIdentity($browser, $identity);
                $this->assertIdentityAuthenticatedOnWebshop($browser, $identity);
                $this->goToIdentityReimbursements($browser);

                $this->assertReimbursementsFilterByStateTabs($browser, $pendingReimbursement, 'pending', [
                    $approvedReimbursement,
                    $declinedReimbursement,
                    $draftReimbursement,
                ]);

                $this->assertReimbursementsFilterByStateTabs($browser, $approvedReimbursement, 'approved', [
                    $pendingReimbursement,
                    $declinedReimbursement,
                    $draftReimbursement,
                ]);

                $this->assertReimbursementsFilterByStateTabs($browser, $declinedReimbursement, 'declined', [
                    $pendingReimbursement,
                    $approvedReimbursement,
                    $draftReimbursement,
                ]);

                $this->assertReimbursementsFilterByStateTabs($browser, $draftReimbursement, 'draft', [
                    $pendingReimbursement,
                    $approvedReimbursement,
                    $declinedReimbursement,
                ]);

                $this->logout($browser);
            });
        }, function () use ($organization) {
            $organization->funds->each(fn (Fund $fund) => $this->deleteFund($fund));
        });
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testReimbursementsFilterByActiveTabs(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity($this->makeUniqueEmail()));
        $implementation = Implementation::byKey('nijmegen');
        $identity = $this->makeIdentity($this->makeUniqueEmail());

        $fundConfig = [
            'allow_reimbursements' => true,
            'implementation_id' => $implementation->id,
        ];

        $voucher1 = $this->makeTestFund($organization, fundConfigsData: $fundConfig)->makeVoucher($identity);
        $voucher2 = $this->makeTestFund($organization, fundConfigsData: $fundConfig)->makeVoucher($identity);

        $activeReimbursement = $this->makeReimbursement($voucher1, true);
        $deactivatedReimbursement = $this->makeReimbursement($voucher2, true);
        $voucher2->deactivate();

        $this->rollbackModels([], function () use ($implementation, $identity, $activeReimbursement, $deactivatedReimbursement) {
            $this->browse(function (Browser $browser) use ($implementation, $identity, $activeReimbursement, $deactivatedReimbursement) {
                $browser->visit($implementation->urlWebshop());

                $this->loginIdentity($browser, $identity);
                $this->assertIdentityAuthenticatedOnWebshop($browser, $identity);
                $this->goToIdentityReimbursements($browser);

                $this->assertReimbursementsFilterByActiveTabs($browser, $activeReimbursement, $deactivatedReimbursement);

                $this->logout($browser);
            });
        }, function () use ($organization) {
            $organization->funds->each(fn (Fund $fund) => $this->deleteFund($fund));
        });
    }

    /**
     * @param Browser $browser
     * @param Reimbursement $reimbursement
     * @param Reimbursement $reimbursementOtherFund
     * @throws ElementClickInterceptedException
     * @throws NoSuchElementException
     * @throws TimeoutException
     * @return ReimbursementsSearchFilterTest
     */
    protected function assertReimbursementsFilterByFund(
        Browser $browser,
        Reimbursement $reimbursement,
        Reimbursement $reimbursementOtherFund,
    ): static {
        $browser->waitFor('@selectControlFunds');
        $browser->click('@selectControlFunds .select-control-search');
        $this->findOptionElement($browser, '@selectControlFunds', $reimbursement->voucher->fund->name)->click();

        $this
            ->assertReimbursementVisible($browser, $reimbursement)
            ->assertReimbursementNotVisible($browser, $reimbursementOtherFund);

        return $this;
    }

    /**
     * @param Browser $browser
     * @param Reimbursement $activeReimbursement
     * @param Reimbursement $inactiveReimbursement
     * @throws ElementClickInterceptedException
     * @throws NoSuchElementException
     * @throws TimeOutException
     * @return void
     */
    protected function assertReimbursementsFilterByActiveTabs(
        Browser $browser,
        Reimbursement $activeReimbursement,
        Reimbursement $inactiveReimbursement
    ): void {
        $browser->waitFor('@reimbursementsFilterActive');
        $browser->click('@reimbursementsFilterActive');

        $this
            ->assertReimbursementVisible($browser, $activeReimbursement)
            ->assertReimbursementNotVisible($browser, $inactiveReimbursement);

        $browser->click('@reimbursementsFilterArchived');

        $this
            ->assertReimbursementVisible($browser, $inactiveReimbursement)
            ->assertReimbursementNotVisible($browser, $activeReimbursement);
    }

    /**
     * @param Browser $browser
     * @param Reimbursement $reimbursement
     * @param string $state
     * @param array $notVisibleReimbursements
     * @throws ElementClickInterceptedException
     * @throws NoSuchElementException
     * @throws TimeOutException
     * @return void
     */
    protected function assertReimbursementsFilterByStateTabs(
        Browser $browser,
        Reimbursement $reimbursement,
        string $state,
        array $notVisibleReimbursements
    ): void {
        $browser->waitFor("@reimbursementsFilterState$state");
        $browser->click("@reimbursementsFilterState$state");

        $this->assertReimbursementVisible($browser, $reimbursement);
        array_walk($notVisibleReimbursements, fn (Reimbursement $r) => $this->assertReimbursementNotVisible($browser, $r));
    }

    /**
     * @param Browser $browser
     * @param Reimbursement $reimbursement
     * @throws TimeoutException
     * @return ReimbursementsSearchFilterTest
     */
    protected function assertReimbursementVisible(Browser $browser, Reimbursement $reimbursement): static
    {
        $browser->waitFor("@listReimbursementsRow$reimbursement->id");
        $browser->assertVisible("@listReimbursementsRow$reimbursement->id");
        $this->assertWebshopRowsCount($browser, 1, '@listReimbursementsContent');

        return $this;
    }

    /**
     * @param Browser $browser
     * @param Reimbursement $reimbursement
     * @throws TimeoutException
     * @return ReimbursementsSearchFilterTest
     */
    protected function assertReimbursementNotVisible(Browser $browser, Reimbursement $reimbursement): static
    {
        $browser->waitUntilMissing("@listReimbursementsRow$reimbursement->id");

        return $this;
    }
}
