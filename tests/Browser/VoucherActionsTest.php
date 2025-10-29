<?php

namespace Browser;

use App\Models\Identity;
use App\Models\Implementation;
use App\Models\Voucher;
use Laravel\Dusk\Browser;
use Tests\Browser\Traits\HasFrontendActions;
use Tests\Browser\Traits\NavigatesFrontendWebshop;
use Tests\Browser\Traits\RollbackModelsTrait;
use Tests\DuskTestCase;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestVouchers;
use Throwable;

class VoucherActionsTest extends DuskTestCase
{
    use MakesTestFunds;
    use MakesTestVouchers;
    use HasFrontendActions;
    use RollbackModelsTrait;
    use NavigatesFrontendWebshop;

    /**
     * @throws Throwable
     */
    public function testVoucherQrCodeAndRelatedActionsVisible(): void
    {
        $implementation = Implementation::byKey('nijmegen');
        $organization = $implementation->organization;

        $fund = $this->makeTestFund($organization, fundConfigsData: [
            'show_qr_code' => true,
        ]);

        $identity = $this->makeIdentity();
        $voucher = $this->makeTestVoucher($fund, $identity);

        $this->assertVoucherQrCodeVisibility($implementation, $identity, $voucher);
    }

    /**
     * @throws Throwable
     */
    public function testProductVoucherQrCodeAndRelatedActionsVisible(): void
    {
        $implementation = Implementation::byKey('nijmegen');
        $organization = $implementation->organization;

        $fund = $this->makeTestFund($organization, fundConfigsData: [
            'show_qr_code' => true,
        ]);

        $identity = $this->makeIdentity();
        $voucher = $this->makeTestVoucher($fund, $identity);

        $provider = $this->makeTestProviderOrganization($this->makeIdentity());
        $product = $this->makeTestProductForReservation($provider);

        $this->makeTestFundProvider($provider, $fund);
        $this->assertVoucherQrCodeVisibility($implementation, $identity, $voucher->buyProductVoucher($product));
    }

    /**
     * @param Implementation $implementation
     * @param Identity $identity
     * @param Voucher $voucher
     * @throws Throwable
     * @return void
     */
    protected function assertVoucherQrCodeVisibility(
        Implementation $implementation,
        Identity $identity,
        Voucher $voucher
    ): void {
        $fund = $voucher->fund;

        $this->rollbackModels([], function () use ($implementation, $identity, $voucher) {
            $this->browse(function (Browser $browser) use ($implementation, $identity, $voucher) {
                $browser->visit($implementation->urlWebshop());

                // Authorize identity
                $this->loginIdentity($browser, $identity);
                $this->assertIdentityAuthenticatedOnWebshop($browser, $identity);

                $this->goToIdentityVouchers($browser);

                $browser
                    ->waitFor("@listVouchersRow$voucher->id")
                    ->click("@listVouchersRow$voucher->id");

                // Assert qr code and buttons visible with default "show_qr_code" value
                $browser->waitFor('@voucherTitle');
                $browser->assertVisible('@voucherQrCode');

                $browser->waitFor('@openVoucherShareModal');
                $browser->click('@openVoucherShareModal');

                $browser->waitFor('@sendVoucherEmail');
                $browser->waitFor('@openVoucherInMeModal');
                $browser->waitFor('@printVoucherQrCodeModal');

                if ($voucher->isProductType()) {
                    $browser->assertVisible('@shareVoucher');
                }

                $voucher->fund->fund_config->forceFill([
                    'show_qr_code' => false,
                ])->save();

                $browser->refresh();
                $browser->waitFor('@voucherTitle');

                // Assert qr code and buttons not visible when "show_qr_code" is false
                $browser->assertMissing('@voucherQrCode');

                if ($voucher->isProductType()) {
                    $browser->assertMissing('@shareVoucher');
                }

                // Logout
                $this->logout($browser);
            });

        }, function () use ($fund) {
            $fund && $this->deleteFund($fund);
        });
    }
}
