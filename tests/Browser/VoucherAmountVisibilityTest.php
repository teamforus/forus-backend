<?php

namespace Tests\Browser;

use App\Models\Fund;
use App\Models\FundPayoutFormula;
use App\Models\Identity;
use App\Models\Implementation;
use App\Models\Product;
use App\Models\Voucher;
use Facebook\WebDriver\Exception\ElementClickInterceptedException;
use Facebook\WebDriver\Exception\NoSuchElementException;
use Facebook\WebDriver\Exception\TimeoutException;
use Laravel\Dusk\Browser;
use Tests\Browser\Traits\HasFrontendActions;
use Tests\Browser\Traits\NavigatesFrontendWebshop;
use Tests\Browser\Traits\RollbackModelsTrait;
use Tests\DuskTestCase;
use Tests\Traits\MakesRequesterVoucherPayouts;
use Tests\Traits\MakesTestFundRequests;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestVouchers;
use Throwable;

class VoucherAmountVisibilityTest extends DuskTestCase
{
    use MakesTestFunds;
    use MakesTestVouchers;
    use HasFrontendActions;
    use RollbackModelsTrait;
    use MakesTestFundRequests;
    use NavigatesFrontendWebshop;
    use MakesRequesterVoucherPayouts;

    /**
     * @throws Throwable
     */
    public function testVoucherAmountVisibilityInVoucherPages(): void
    {
        $implementation = Implementation::byKey('nijmegen');
        $organization = $implementation->organization;
        $fund = $this->makeTestFund($organization, fundConfigsData: ['hide_voucher_amount' => false]);

        $identity = $this->makeIdentity($this->makeUniqueEmail());
        $voucher = $fund->makeVoucher($identity);

        $this->rollbackModels([], function () use ($implementation, $identity, $voucher) {
            $this->browse(function (Browser $browser) use ($implementation, $identity, $voucher) {
                $browser->visit($implementation->urlWebshop());

                // Authorize identity
                $this->loginIdentity($browser, $identity);
                $this->assertIdentityAuthenticatedOnWebshop($browser, $identity);

                $this->goToIdentityVouchers($browser);

                $browser->waitFor("@listVouchersRow$voucher->id");
                $browser->within("@listVouchersRow$voucher->id", fn (Browser $b) => $b->assertVisible('@voucherAmount'));

                $browser->click("@listVouchersRow$voucher->id");

                // Assert amount visible with "hide_voucher_amount" value "false"
                $browser->waitFor('@voucherTitle');
                $browser->assertVisible('@voucherAmount');

                $voucher->fund->fund_config->forceFill([
                    'hide_voucher_amount' => true,
                ])->save();

                $this->goToIdentityVouchers($browser);

                // Assert amount is hidden with "hide_voucher_amount" value "true"
                $browser->waitFor("@listVouchersRow$voucher->id");
                $browser->within("@listVouchersRow$voucher->id", fn (Browser $b) => $b->assertMissing('@voucherAmount'));

                $browser->click("@listVouchersRow$voucher->id");

                $browser->waitFor('@voucherTitle');
                $browser->assertMissing('@voucherAmount');

                // Logout
                $this->logout($browser);
            });

        }, function () use ($fund) {
            $fund && $this->deleteFund($fund);
        });
    }

    /**
     * @throws Throwable
     */
    public function testVoucherAmountVisibilityInFundRequestPage(): void
    {
        $implementation = Implementation::byKey('nijmegen');
        $organization = $implementation->organization;

        $identity = $this->makeIdentity($this->makeUniqueEmail());
        $employee = $organization->employees[0];

        $fund = $this
            ->makeTestFund($organization, fundConfigsData: ['hide_voucher_amount' => false])
            ->syncCriteria([[
                'record_type_key' => 'children_nth',
                'operator' => '>',
                'value' => 2,
                'show_attachment' => false,
            ]])
            ->refresh();

        $fundRequest = $this
            ->setCriteriaAndMakeFundRequest($identity, $fund, ['children_nth' => 3])
            ->assignEmployee($employee)
            ->approve();

        $voucher = $fundRequest->vouchers()->first();
        $this->assertNotNull($voucher);

        $this->rollbackModels([], function () use ($implementation, $identity, $voucher) {
            $this->browse(function (Browser $browser) use ($implementation, $identity, $voucher) {
                $browser->visit($implementation->urlWebshop());

                // Authorize identity
                $this->loginIdentity($browser, $identity);
                $this->assertIdentityAuthenticatedOnWebshop($browser, $identity);

                $this->assertAmountVisibilityInFundRequest($browser, $voucher, true);

                $voucher->fund->fund_config->forceFill([
                    'hide_voucher_amount' => true,
                ])->save();

                $this->assertAmountVisibilityInFundRequest($browser, $voucher, false);

                // Logout
                $this->logout($browser);
            });

        }, function () use ($fund) {
            $fund && $this->deleteFund($fund);
        });
    }

    /**
     * @throws Throwable
     */
    public function testVoucherAmountVisibilityInReservationModal(): void
    {
        $implementation = Implementation::byKey('nijmegen');
        $organization = $implementation->organization;
        $fund = $this->makeTestFund($organization, fundConfigsData: ['hide_voucher_amount' => false]);

        $identity = $this->makeIdentity($this->makeUniqueEmail());
        $voucher = $fund->makeVoucher($identity);

        $provider = $this->makeTestProviderOrganization($this->makeIdentity());
        $product = $this->makeTestProductForReservation($provider);
        $this->makeTestFundProvider($provider, $fund);

        $this->rollbackModels([], function () use ($implementation, $identity, $voucher, $product, $fund) {
            $this->browse(function (Browser $browser) use ($implementation, $identity, $voucher, $product, $fund) {
                $browser->visit($implementation->urlWebshop());

                // Authorize identity
                $this->loginIdentity($browser, $identity);
                $this->assertIdentityAuthenticatedOnWebshop($browser, $identity);

                $this->goToVoucher($browser, $voucher);
                $this->assertAmountVisibilityInReservationModal($browser, $fund, $identity, $product, true);

                $voucher->fund->fund_config->forceFill([
                    'hide_voucher_amount' => true,
                ])->save();

                $this->goToVoucher($browser, $voucher);
                $this->assertAmountVisibilityInReservationModal($browser, $fund, $identity, $product, false);

                // Logout
                $this->logout($browser);
            });

        }, function () use ($fund) {
            $fund && $this->deleteFund($fund);
        });
    }

    /**
     * @throws Throwable
     */
    public function testVoucherAmountVisibilityInReimbursementForm(): void
    {
        $implementation = Implementation::byKey('nijmegen');
        $organization = $implementation->organization;

        $fund = $this->makeTestFund($organization, fundConfigsData: [
            'hide_voucher_amount' => false,
            'allow_reimbursements' => true,
        ]);

        $identity = $this->makeIdentity($this->makeUniqueEmail());
        $voucher = $fund->makeVoucher($identity);

        $this->rollbackModels([], function () use ($implementation, $identity, $voucher) {
            $this->browse(function (Browser $browser) use ($implementation, $identity, $voucher) {
                $browser->visit($implementation->urlWebshop());

                // Authorize identity
                $this->loginIdentity($browser, $identity);
                $this->assertIdentityAuthenticatedOnWebshop($browser, $identity);

                $this->assertAmountVisibilityInReimbursement($browser, $voucher, true);

                $voucher->fund->fund_config->forceFill([
                    'hide_voucher_amount' => true,
                ])->save();

                $this->assertAmountVisibilityInReimbursement($browser, $voucher, false);

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
    public function testVoucherAmountVisibilityInPayouts()
    {
        $implementation = Implementation::byKey('nijmegen');
        $organization = $implementation->organization;
        $organizationState = $organization->only(['fund_request_resolve_policy', 'allow_profiles']);

        $organization->forceFill(['allow_profiles' => true])->save();

        $fund = $this->makePayoutEnabledFund($organization, $implementation, fundConfigsData: [
            'hide_voucher_amount' => false,
        ]);

        $fund->fund_payout_formulas()->create([
            'type' => FundPayoutFormula::TYPE_FIXED,
            'amount' => 50.00,
        ]);

        $fund->fund_formulas()->update(['amount' => 100]);

        $identity = $this->makeIdentity($this->makeUniqueEmail(), bsn: $this->randomFakeBsn());
        $this->makePayoutVoucherViaApplication($identity, $fund);
        $voucher = $identity->vouchers()->first();
        $this->assertNotNull($voucher);

        $this->rollbackModels([
            [$organization, $organizationState],
        ], function () use ($implementation, $identity, $voucher) {
            $this->browse(function (Browser $browser) use ($implementation, $identity, $voucher) {
                $browser->visit($implementation->urlWebshop());

                $this->loginIdentity($browser, $identity);
                $this->assertIdentityAuthenticatedOnWebshop($browser, $identity);

                $browser->visit($implementation->urlWebshop('payouts'));

                $this->assertAmountVisibilityInPayoutModal($browser, $voucher, true);

                $voucher->fund->fund_config->forceFill([
                    'hide_voucher_amount' => true,
                ])->save();

                $browser->refresh();

                $this->assertAmountVisibilityInPayoutModal($browser, $voucher, false);

                // close modal and logout
                $browser->click('.modal-close');
                $this->logout($browser);
            });
        }, function () use ($fund) {
            $fund && $this->deleteFund($fund);
        });
    }

    /**
     * @param Browser $browser
     * @param Voucher $voucher
     * @param bool $assertVisible
     * @throws TimeoutException
     * @return void
     */
    protected function assertAmountVisibilityInReimbursement(
        Browser $browser,
        Voucher $voucher,
        bool $assertVisible
    ): void {
        $this->goToReimbursementsPage($browser);

        $browser->waitFor('@reimbursementsEmptyBlock');
        $browser->assertVisible('@reimbursementsEmptyBlock');
        $browser->waitFor('@btnEmptyBlock');
        $browser->press('@btnEmptyBlock');

        $browser->waitFor('@reimbursementEditContent');

        $this->assertVisibilityInForm($browser, $voucher, $assertVisible, '@reimbursementForm');
    }

    /**
     * @param Browser $browser
     * @param Voucher $voucher
     * @param bool $assertVisible
     * @throws TimeoutException
     * @return void
     */
    protected function assertAmountVisibilityInPayoutModal(
        Browser $browser,
        Voucher $voucher,
        bool $assertVisible
    ): void {
        $browser->waitFor('@payoutsEmptyBlock');
        $browser->within('@payoutsEmptyBlock', function (Browser $browser) {
            $browser->press('@btnEmptyBlock');
        });

        $this->assertVisibilityInForm(
            $browser,
            $voucher,
            $assertVisible,
            '@voucherPayoutForm',
            '@voucherPayoutVoucherSelect'
        );
    }

    /**
     * @param Browser $browser
     * @param Voucher $voucher
     * @param bool $assertVisible
     * @param string $formSelector
     * @param string $selectSelector
     * @return void
     * @throws TimeoutException
     */
    private function assertVisibilityInForm(
        Browser $browser,
        Voucher $voucher,
        bool $assertVisible,
        string $formSelector,
        string $selectSelector = '@voucherSelector'
    ): void {
        $browser->waitFor($formSelector);

        $browser->within($formSelector, function (Browser $browser) use ($voucher, $assertVisible, $selectSelector) {
            $browser->waitFor($selectSelector);
            $browser->press($selectSelector);
            $browser->waitFor('@voucherSelectorOptions');

            $browser->waitFor("@voucherSelectorOption$voucher->id");

            $browser->within("@voucherSelectorOption$voucher->id", function (Browser $browser) use ($voucher, $assertVisible) {
                $assertVisible
                    ? $browser->assertVisible('@voucherAmount')
                    : $browser->assertMissing('@voucherAmount');
            });

            $browser->press("@voucherSelectorOption$voucher->id");

            $browser->within($selectSelector, function (Browser $browser) use ($voucher, $assertVisible) {
                $assertVisible
                    ? $browser->assertVisible('@voucherAmount')
                    : $browser->assertMissing('@voucherAmount');
            });
        });
    }

    /**
     * @param Browser $browser
     * @param Voucher $voucher
     * @throws ElementClickInterceptedException
     * @throws NoSuchElementException
     * @throws TimeoutException
     * @return void
     */
    private function goToVoucher(Browser $browser, Voucher $voucher): void
    {
        $this->goToIdentityVouchers($browser);

        $browser->waitFor("@listVouchersRow$voucher->id");
        $browser->click("@listVouchersRow$voucher->id");
        $browser->waitFor('@voucherTitle');
    }

    /**
     * @param Browser $browser
     * @param Fund $fund
     * @param Identity $identity
     * @param Product $product
     * @param bool $assertVisible
     * @throws ElementClickInterceptedException
     * @throws NoSuchElementException
     * @throws TimeoutException
     * @return void
     */
    private function assertAmountVisibilityInReservationModal(
        Browser $browser,
        Fund $fund,
        Identity $identity,
        Product $product,
        bool $assertVisible
    ): void {
        $browser->waitFor("@listProductsRow$product->id")->press("@listProductsRow$product->id");
        $browser->waitFor("@listFundsRow$fund->id");

        $browser->waitFor('@productName');
        $browser->assertSeeIn('@productName', $product->name);

        $browser->click("@listFundsRow$fund->id @reserveProduct");
        $browser->waitFor('@modalProductReserve');

        $browser->within('@modalProductReserve', function (Browser $browser) use ($identity, $assertVisible) {
            $browser->waitFor('@btnSelectVoucher');

            $assertVisible
                ? $browser->assertVisible('@voucherAmount')
                : $browser->assertMissing('@voucherAmount');

            $browser->click('@closeModalButton');
        });
    }

    /**
     * @param Browser $browser
     * @param Voucher $voucher
     * @param bool $assertVisible
     * @throws ElementClickInterceptedException
     * @throws NoSuchElementException
     * @throws TimeoutException
     * @return void
     */
    private function assertAmountVisibilityInFundRequest(
        Browser $browser,
        Voucher $voucher,
        bool $assertVisible
    ): void {
        $request = $voucher->fund_request;
        $this->goToIdentityFundRequests($browser);

        $element = '@listFundRequestsRow' . $request->id;
        $browser->waitFor($element);
        $browser->assertSeeIn($element, $request->fund->name);

        $browser->click($element);
        $browser->waitFor('@fundRequestFund');
        $browser->assertSeeIn('@fundRequestFund', $request->fund->name);

        $browser->waitFor("@listVouchersRow$voucher->id");

        $browser->within("@listVouchersRow$voucher->id", function (Browser $browser) use ($assertVisible) {
            $assertVisible
                ? $browser->assertVisible('@voucherAmount')
                : $browser->assertMissing('@voucherAmount');
        });
    }
}
