<?php

namespace Tests\Browser\Filters\Webshop;

use App\Models\Fund;
use App\Models\Implementation;
use App\Models\Voucher;
use Facebook\WebDriver\Exception\ElementClickInterceptedException;
use Facebook\WebDriver\Exception\NoSuchElementException;
use Facebook\WebDriver\Exception\TimeOutException;
use Laravel\Dusk\Browser;
use Tests\Browser\Traits\HasFrontendActions;
use Tests\Browser\Traits\RollbackModelsTrait;
use Tests\DuskTestCase;
use Tests\Traits\MakesTestFunds;
use Throwable;

class VouchersSearchFilterTest extends DuskTestCase
{
    use MakesTestFunds;
    use HasFrontendActions;
    use RollbackModelsTrait;

    /**
     * @throws Throwable
     * @return void
     */
    public function testVouchersFilterByActiveTabs(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity($this->makeUniqueEmail()));
        $implementation = Implementation::byKey('nijmegen');
        $identity = $organization->identity;

        $fundConfigsData = [
            'implementation_id' => $implementation->id,
        ];

        $fund = $this->makeTestFund($organization, fundConfigsData: $fundConfigsData);
        $activeVoucher = $fund->makeVoucher($identity);

        $fund2 = $this->makeTestFund($organization, fundConfigsData: $fundConfigsData);
        $deactivatedVoucher = $fund2->makeVoucher($identity)->deactivate();

        $this->rollbackModels([], function () use (
            $implementation,
            $identity,
            $activeVoucher,
            $deactivatedVoucher
        ) {
            $this->browse(function (Browser $browser) use (
                $implementation,
                $identity,
                $activeVoucher,
                $deactivatedVoucher
            ) {
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

        $this
            ->assertVoucherVisible($browser, $activeVoucher)
            ->assertVoucherNotVisible($browser, $inactiveVoucher);

        $browser->click('@vouchersFilterArchived');

        $this
            ->assertVoucherVisible($browser, $inactiveVoucher)
            ->assertVoucherNotVisible($browser, $activeVoucher);
    }

    /**
     * @param Browser $browser
     * @param Voucher $voucher
     * @param int $count
     * @throws TimeoutException
     * @return VouchersSearchFilterTest
     */
    protected function assertVoucherVisible(Browser $browser, Voucher $voucher, int $count = 1): static
    {
        $browser->waitFor("@listVouchersRow$voucher->id");
        $browser->assertVisible("@listVouchersRow$voucher->id");
        $this->assertWebshopRowsCount($browser, $count, '@listVouchersContent');

        return $this;
    }

    /**
     * @param Browser $browser
     * @param Voucher $voucher
     * @throws TimeoutException
     * @return VouchersSearchFilterTest
     */
    protected function assertVoucherNotVisible(Browser $browser, Voucher $voucher): static
    {
        $browser->waitUntilMissing("@listVouchersRow$voucher->id");

        return $this;
    }
}
