<?php

namespace Tests\Browser\Filters\Webshop;

use App\Models\Fund;
use App\Models\Implementation;
use App\Models\Reimbursement;
use Facebook\WebDriver\Exception\ElementClickInterceptedException;
use Facebook\WebDriver\Exception\NoSuchElementException;
use Facebook\WebDriver\Exception\TimeOutException;
use Laravel\Dusk\Browser;
use Throwable;

class ReimbursementsWebshopSearchFilterTest extends BaseWebshopSearchFilter
{
    /**
     * @return string
     */
    public function getListSelector(): string
    {
        return '@listReimbursements';
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testReimbursementsFilter(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity($this->makeUniqueEmail()));
        $implementation = Implementation::byKey('nijmegen');

        $identity = $this->makeIdentity($this->makeUniqueEmail());

        $voucher1 = $this->makeTestFund(
            $organization,
            fundConfigsData: ['allow_reimbursements' => true],
            implementation: $implementation,
        )->makeVoucher($identity);

        $voucher2 = $this->makeTestFund(
            $organization,
            fundConfigsData: ['allow_reimbursements' => true],
            implementation: $implementation,
        )->makeVoucher($identity);

        $reimbursement = $this->makeReimbursement($voucher1, true);
        $reimbursement2 = $this->makeReimbursement($voucher2, true);

        $this->rollbackModels([], function () use ($implementation, $identity, $reimbursement, $reimbursement2) {
            $this->browse(function (Browser $browser) use ($implementation, $identity, $reimbursement, $reimbursement2) {
                $browser->visit($implementation->urlWebshop());

                $this->loginIdentity($browser, $identity);
                $this->assertIdentityAuthenticatedOnWebshop($browser, $identity);
                $this->goToIdentityReimbursements($browser);

                $this->assertListFilterByFund($browser, $reimbursement->voucher->fund, $reimbursement->id, 1);
                $this->assertListFilterByFund($browser, $reimbursement2->voucher->fund, $reimbursement2->id, 1);

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
        $identity = $this->makeIdentity($this->makeUniqueEmail());

        $this->rollbackModels([], function () use ($organization, $identity) {
            $this->browse(function (Browser $browser) use ($organization, $identity) {
                $employee = $organization->findEmployee($organization->identity);
                $implementation = Implementation::byKey('nijmegen');

                $fundConfig = [
                    'allow_reimbursements' => true,
                    'implementation_id' => Implementation::byKey('nijmegen')->id,
                ];

                $voucher1 = $this->makeTestFund($organization, fundConfigsData: $fundConfig)->makeVoucher($identity);
                $voucher2 = $this->makeTestFund($organization, fundConfigsData: $fundConfig)->makeVoucher($identity);
                $voucher3 = $this->makeTestFund($organization, fundConfigsData: $fundConfig)->makeVoucher($identity);
                $voucher4 = $this->makeTestFund($organization, fundConfigsData: $fundConfig)->makeVoucher($identity);

                $pendingReimbursement = $this->makeReimbursement($voucher1, true);
                $approvedReimbursement = $this->makeReimbursement($voucher2, true)->assign($employee)->approve();
                $declinedReimbursement = $this->makeReimbursement($voucher3, true)->assign($employee)->decline();
                $draftReimbursement = $this->makeReimbursement($voucher4, false);

                $browser->visit($implementation->urlWebshop());

                $this->loginIdentity($browser, $identity);
                $this->assertIdentityAuthenticatedOnWebshop($browser, $identity);
                $this->goToIdentityReimbursements($browser);

                $this->assertReimbursementsFilterByStateTabs($browser, $pendingReimbursement, 'pending');
                $this->assertReimbursementsFilterByStateTabs($browser, $approvedReimbursement, 'approved');
                $this->assertReimbursementsFilterByStateTabs($browser, $declinedReimbursement, 'declined');
                $this->assertReimbursementsFilterByStateTabs($browser, $draftReimbursement, 'draft');

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

        $this->rollbackModels([], function () use ($organization) {
            $this->browse(function (Browser $browser) use ($organization) {
                $identity = $this->makeIdentity($this->makeUniqueEmail());
                $implementation = Implementation::byKey('nijmegen');
                $fundConfig = ['allow_reimbursements' => true, 'implementation_id' => $implementation->id];

                $voucher1 = $this->makeTestFund($organization, fundConfigsData: $fundConfig)->makeVoucher($identity);
                $voucher2 = $this->makeTestFund($organization, fundConfigsData: $fundConfig)->makeVoucher($identity);

                $activeReimbursement = $this->makeReimbursement($voucher1, true);
                $deactivatedReimbursement = $this->makeReimbursement($voucher2, true);
                $voucher2->deactivate();

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
        $this->assertListVisibility($browser, $activeReimbursement->id, true, 1);

        $browser->click('@reimbursementsFilterArchived');
        $this->assertListVisibility($browser, $inactiveReimbursement->id, true, 1);
    }

    /**
     * @param Browser $browser
     * @param Reimbursement $reimbursement
     * @param string $state
     * @throws ElementClickInterceptedException
     * @throws NoSuchElementException
     * @throws TimeoutException
     * @return void
     */
    protected function assertReimbursementsFilterByStateTabs(Browser $browser, Reimbursement $reimbursement, string $state): void
    {
        $browser->waitFor("@reimbursementsFilterState$state");
        $browser->click("@reimbursementsFilterState$state");

        $this->assertListVisibility($browser, $reimbursement->id, true, 1);
    }
}
