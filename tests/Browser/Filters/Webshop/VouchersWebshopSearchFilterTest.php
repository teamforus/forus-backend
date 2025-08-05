<?php

namespace Tests\Browser\Filters\Webshop;

use App\Models\Fund;
use App\Models\Implementation;
use App\Models\Voucher;
use Facebook\WebDriver\Exception\ElementClickInterceptedException;
use Facebook\WebDriver\Exception\NoSuchElementException;
use Facebook\WebDriver\Exception\TimeOutException;
use Laravel\Dusk\Browser;
use Throwable;

class VouchersWebshopSearchFilterTest extends BaseWebshopSearchFilter
{
    /**
     * @return string
     */
    public function getListSelector(): string
    {
        return '@listVouchers';
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testVouchersFilterByActiveTabs(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity($this->makeUniqueEmail()));

        $this->rollbackModels([], function () use ($organization) {
            $this->browse(function (Browser $browser) use ($organization) {
                $implementation = Implementation::byKey('nijmegen');
                $identity = $this->makeIdentity();

                $fund = $this->makeTestFund($organization, implementation: $implementation);
                $fund2 = $this->makeTestFund($organization, implementation: $implementation);

                $activeVoucher = $fund->makeVoucher($identity);
                $deactivatedVoucher = $fund2->makeVoucher($identity)->deactivate();

                $browser->visit($implementation->urlWebshop());

                $this->loginIdentity($browser, $identity);
                $this->assertIdentityAuthenticatedOnWebshop($browser, $identity);
                $this->goToIdentityVouchers($browser);

                $this->assertVouchersFilterByActiveTabs($browser, $activeVoucher, $deactivatedVoucher);

                $this->logout($browser);
            });
        }, function () use ($organization) {
            $organization->funds->each(fn (Fund $fund) => $this->deleteFund($fund));
        });
    }

    /**
     * @param Browser $browser
     * @param Voucher $activeVoucher
     * @param Voucher $inactiveVoucher
     * @throws ElementClickInterceptedException
     * @throws NoSuchElementException
     * @throws TimeOutException
     * @return void
     */
    protected function assertVouchersFilterByActiveTabs(
        Browser $browser,
        Voucher $activeVoucher,
        Voucher $inactiveVoucher
    ): void {
        $browser->waitFor('@vouchersFilterActive');
        $browser->click('@vouchersFilterActive');
        $this->assertListVisibility($browser, $activeVoucher->id, true, 1);

        $browser->click('@vouchersFilterArchived');
        $this->assertListVisibility($browser, $inactiveVoucher->id, true, 1);
    }
}
