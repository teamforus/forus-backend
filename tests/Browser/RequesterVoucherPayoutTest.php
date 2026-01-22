<?php

namespace Tests\Browser;

use App\Models\Implementation;
use App\Models\FundPayoutFormula;
use App\Models\VoucherTransaction;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Dusk\Browser;
use Tests\Browser\Traits\HasFrontendActions;
use Tests\Browser\Traits\NavigatesFrontendWebshop;
use Tests\Browser\Traits\RollbackModelsTrait;
use Tests\DuskTestCase;
use Tests\Traits\MakesRequesterVoucherPayouts;
use Tests\Traits\MakesTestFunds;
use Throwable;

class RequesterVoucherPayoutTest extends DuskTestCase
{
    use WithFaker;
    use MakesTestFunds;
    use MakesRequesterVoucherPayouts;
    use HasFrontendActions;
    use RollbackModelsTrait;
    use NavigatesFrontendWebshop;

    /**
     * @throws Throwable
     */
    public function testRequesterVoucherPayoutFlow(): void
    {
        $implementation = Implementation::byKey('nijmegen');
        $organization = $implementation->organization;
        $organizationState = $organization->only(['fund_request_resolve_policy', 'allow_profiles']);

        $organization->forceFill(['allow_profiles' => true])->save();
        $fund = $this->makePayoutEnabledFund($organization, $implementation);

        $identity = $this->makeIdentity($this->makeUniqueEmail(), bsn: $this->randomFakeBsn());

        $result = $this->makePayoutVoucherViaApplication($identity, $fund);
        $voucher = $result['voucher'];
        $iban = $result['iban'];
        $ibanName = $result['iban_name'];

        $this->rollbackModels([
            [$organization, $organizationState],
        ], function () use ($implementation, $identity, $voucher, $iban, $ibanName) {
            $this->browse(function (Browser $browser) use ($implementation, $identity, $voucher, $iban, $ibanName) {
                $browser->visit($implementation->urlWebshop());

                $this->loginIdentity($browser, $identity);
                $this->assertIdentityAuthenticatedOnWebshop($browser, $identity);

                $this->goToIdentityVouchers($browser);
                $browser->waitFor("@listVouchersRow$voucher->id");
                $browser->click("@listVouchersRow$voucher->id");

                $browser->waitFor('@voucherTitle');
                $browser->waitFor('@openVoucherPayoutModal');
                $browser->press('@openVoucherPayoutModal');

                $browser->waitFor('@voucherPayoutForm');
                $browser->waitFor('@voucherPayoutAmount');
                $browser->typeSlowly('@voucherPayoutAmount', '50.00', 20);
                $browser->press('@voucherPayoutAcceptRules');
                $browser->press('@voucherPayoutSubmit');

                $browser->waitFor('@voucherPayoutSuccess');

                $transaction = VoucherTransaction::where('voucher_id', $voucher->id)
                    ->where('target', VoucherTransaction::TARGET_PAYOUT)
                    ->where('initiator', VoucherTransaction::INITIATOR_REQUESTER)
                    ->latest('id')
                    ->first();

                $this->assertNotNull($transaction);
                $this->assertEquals(50.00, (float) $transaction->amount);
                $this->assertEquals($iban, $transaction->target_iban);
                $this->assertEquals($ibanName, $transaction->target_name);

                $browser->press('@voucherPayoutSuccessClose');

                $browser->visit($implementation->urlWebshop('uitbetalingen'));
                $browser->waitFor("@listPayoutsRow$transaction->id");

                $this->logout($browser);
            });
        }, function () use ($fund) {
            $fund && $this->deleteFund($fund);
        });
    }

    /**
     * @throws Throwable
     */
    public function testRequesterVoucherPayoutSelectsOtherFundRequest(): void
    {
        $implementation = Implementation::byKey('nijmegen');
        $organization = $implementation->organization;
        $organizationState = $organization->only(['fund_request_resolve_policy', 'allow_profiles']);

        $organization->forceFill(['allow_profiles' => true])->save();
        $fund1 = $this->makePayoutEnabledFund($organization, $implementation);
        $fund2 = $this->makePayoutEnabledFund($organization, $implementation);

        $identity = $this->makeIdentity($this->makeUniqueEmail(), bsn: $this->randomFakeBsn());

        $result1 = $this->makePayoutVoucherViaApplication($identity, $fund1);
        $result1['fund_request']->forceFill(['created_at' => now()->subDay()])->save();

        $result2 = $this->makePayoutVoucherViaApplication($identity, $fund2);
        $voucher = $result2['voucher'];

        $this->rollbackModels([
            [$organization, $organizationState],
        ], function () use ($implementation, $identity, $voucher, $result1) {
            $this->browse(function (Browser $browser) use ($implementation, $identity, $voucher, $result1) {
                $browser->visit($implementation->urlWebshop());

                $this->loginIdentity($browser, $identity);
                $this->assertIdentityAuthenticatedOnWebshop($browser, $identity);

                $this->goToIdentityVouchers($browser);
                $browser->waitFor("@listVouchersRow$voucher->id");
                $browser->click("@listVouchersRow$voucher->id");

                $browser->waitFor('@voucherTitle');
                $browser->waitFor('@openVoucherPayoutModal');
                $browser->press('@openVoucherPayoutModal');

                $browser->waitFor('@voucherPayoutForm');
                $browser->waitFor('@voucherPayoutFundRequestSelect');
                $browser->click('@voucherPayoutFundRequestSelect');
                $browser->waitFor('@voucherPayoutFundRequestSelectOptions');
                $browser->within('@voucherPayoutFundRequestSelectOptions', function (Browser $browser) {
                    $browser->click('.select-control-option:last-child');
                });

                $browser->waitFor('@voucherPayoutAmount');
                $browser->typeSlowly('@voucherPayoutAmount', '50.00', 20);
                $browser->press('@voucherPayoutAcceptRules');
                $browser->press('@voucherPayoutSubmit');

                $browser->waitFor('@voucherPayoutSuccess');

                $transaction = VoucherTransaction::where('voucher_id', $voucher->id)
                    ->where('target', VoucherTransaction::TARGET_PAYOUT)
                    ->where('initiator', VoucherTransaction::INITIATOR_REQUESTER)
                    ->latest('id')
                    ->first();

                $this->assertNotNull($transaction);
                $this->assertEquals($result1['iban'], $transaction->target_iban);
                $this->assertEquals($result1['iban_name'], $transaction->target_name);

                $browser->press('@voucherPayoutSuccessClose');

                $this->logout($browser);
            });
        }, function () use ($fund1, $fund2) {
            $fund1 && $this->deleteFund($fund1);
            $fund2 && $this->deleteFund($fund2);
        });
    }

    /**
     * @throws Throwable
     */
    public function testRequesterVoucherPayoutHiddenWithoutFundRequests(): void
    {
        $implementation = Implementation::byKey('nijmegen');
        $organization = $implementation->organization;
        $organizationState = $organization->only(['fund_request_resolve_policy', 'allow_profiles']);

        $organization->forceFill(['allow_profiles' => true])->save();
        $fund = $this->makePayoutEnabledFund($organization, $implementation);

        $identity = $this->makeIdentity($this->makeUniqueEmail(), bsn: $this->randomFakeBsn());
        $voucher = $fund->makeVoucher(identity: $identity, amount: 100);

        $this->rollbackModels([
            [$organization, $organizationState],
        ], function () use ($implementation, $identity, $voucher) {
            $this->browse(function (Browser $browser) use ($implementation, $identity, $voucher) {
                $browser->visit($implementation->urlWebshop());

                $this->loginIdentity($browser, $identity);
                $this->assertIdentityAuthenticatedOnWebshop($browser, $identity);

                $this->goToIdentityVouchers($browser);
                $browser->waitFor("@listVouchersRow$voucher->id");
                $browser->click("@listVouchersRow$voucher->id");

                $browser->waitFor('@voucherTitle');
                $browser->assertMissing('@openVoucherPayoutModal');

                $this->logout($browser);
            });
        }, function () use ($fund) {
            $fund && $this->deleteFund($fund);
        });
    }

    /**
     * @throws Throwable
     */
    public function testRequesterVoucherPayoutFromPayoutsTabShowsAmountWarning(): void
    {
        $implementation = Implementation::byKey('nijmegen');
        $organization = $implementation->organization;
        $organizationState = $organization->only(['fund_request_resolve_policy', 'allow_profiles']);

        $organization->forceFill(['allow_profiles' => true])->save();
        $fund = $this->makePayoutEnabledFund($organization, $implementation);
        $fund->fund_payout_formulas()->create([
            'type' => FundPayoutFormula::TYPE_FIXED,
            'amount' => 50.00,
        ]);
        $fund->fund_formulas()->update(['amount' => 10]);

        $identity = $this->makeIdentity($this->makeUniqueEmail(), bsn: $this->randomFakeBsn());
        $this->makePayoutVoucherViaApplication($identity, $fund);

        $this->rollbackModels([
            [$organization, $organizationState],
        ], function () use ($implementation, $identity) {
            $this->browse(function (Browser $browser) use ($implementation, $identity) {
                $browser->visit($implementation->urlWebshop());

                $this->loginIdentity($browser, $identity);
                $this->assertIdentityAuthenticatedOnWebshop($browser, $identity);

                $browser->visit($implementation->urlWebshop('payouts'));
                $browser->waitFor('@payoutsEmptyBlock');
                $browser->within('@payoutsEmptyBlock', function (Browser $browser) {
                    $browser->press('@btnEmptyBlock');
                });

                $browser->waitFor('@voucherPayoutForm');
                $browser->waitFor('.block-warning');
                $browser->assertMissing('@voucherPayoutAmount');
                $browser->assertDisabled('@voucherPayoutSubmit');

                $browser->click('.modal-close');

                $this->logout($browser);
            });
        }, function () use ($fund) {
            $fund && $this->deleteFund($fund);
        });
    }

    /**
     * @throws Throwable
     */
    public function testRequesterVoucherPayoutFromProductTabShowsCountWarning(): void
    {
        $implementation = Implementation::byKey('nijmegen');
        $organization = $implementation->organization;
        $organizationState = $organization->only(['fund_request_resolve_policy', 'allow_profiles']);

        $organization->forceFill(['allow_profiles' => true])->save();
        $fund = $this->makePayoutEnabledFund($organization, $implementation);
        $fund->fund_config->forceFill([
            'allow_voucher_payout_count' => 1,
        ])->save();

        $products = $this->makeTestProviderWithProducts(1);
        $product = $products[0];
        $this->addProductToFund($fund, $product, false);

        $identity = $this->makeIdentity($this->makeUniqueEmail(), bsn: $this->randomFakeBsn());
        $result = $this->makePayoutVoucherViaApplication($identity, $fund);
        $voucher = $result['voucher'];
        $iban = $result['iban'];
        $ibanName = $result['iban_name'];

        $voucher->makeTransaction([
            'target' => VoucherTransaction::TARGET_PAYOUT,
            'initiator' => VoucherTransaction::INITIATOR_REQUESTER,
            'amount' => 10,
            'target_iban' => $iban,
            'target_name' => $ibanName,
            'state' => VoucherTransaction::STATE_SUCCESS,
        ]);

        $this->rollbackModels([
            [$organization, $organizationState],
            [$implementation, $implementation->only(['voucher_payout_informational_product_id'])],
        ], function () use ($implementation, $identity, $product) {
            $implementation->forceFill([
                'voucher_payout_informational_product_id' => $product->id,
            ])->save();

            $this->browse(function (Browser $browser) use ($implementation, $identity, $product) {
                $browser->visit($implementation->urlWebshop());

                $this->loginIdentity($browser, $identity);
                $this->assertIdentityAuthenticatedOnWebshop($browser, $identity);

                $browser->visit($implementation->urlWebshop("products/$product->id"));
                $browser->waitFor('@productName');
                $browser->waitFor('@openProductPayoutModal');
                $browser->press('@openProductPayoutModal');

                $browser->waitFor('@voucherPayoutForm');
                $browser->waitFor('.block-warning');
                $browser->assertMissing('@voucherPayoutAmount');
                $browser->assertDisabled('@voucherPayoutSubmit');

                $browser->click('.modal-close');

                $this->logout($browser);
            });
        }, function () use ($fund) {
            $fund && $this->deleteFund($fund);
        });
    }

    /**
     * @throws Throwable
     */
    public function testProductPayoutButtonVisibleForInformationalProduct(): void
    {
        $implementation = Implementation::byKey('nijmegen');
        $organization = $implementation->organization;
        $organizationState = $organization->only(['fund_request_resolve_policy', 'allow_profiles']);

        $organization->forceFill(['allow_profiles' => true])->save();
        $fund = $this->makePayoutEnabledFund($organization, $implementation);

        $products = $this->makeTestProviderWithProducts(1);
        $product = $products[0];
        $this->addProductToFund($fund, $product, false);

        $identity = $this->makeIdentity($this->makeUniqueEmail(), bsn: $this->randomFakeBsn());
        $this->makePayoutVoucherViaApplication($identity, $fund);

        $this->rollbackModels([
            [$organization, $organizationState],
            [$implementation, $implementation->only(['voucher_payout_informational_product_id'])],
        ], function () use ($implementation, $identity, $product) {
            $implementation->forceFill([
                'voucher_payout_informational_product_id' => $product->id,
            ])->save();

            $this->browse(function (Browser $browser) use ($implementation, $identity, $product) {
                $browser->visit($implementation->urlWebshop());

                $this->loginIdentity($browser, $identity);
                $this->assertIdentityAuthenticatedOnWebshop($browser, $identity);

                $browser->visit($implementation->urlWebshop("products/$product->id"));
                $browser->waitFor('@productName');
                $browser->waitFor('@openProductPayoutModal');
                $browser->assertPresent('@openProductPayoutModal');

                $this->logout($browser);
            });
        }, function () use ($fund) {
            $fund && $this->deleteFund($fund);
        });
    }
}
