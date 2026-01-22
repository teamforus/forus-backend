<?php

namespace Tests\Browser;

use App\Models\Identity;
use App\Models\Implementation;
use App\Models\Organization;
use App\Models\Voucher;
use App\Models\VoucherTransaction;
use Facebook\WebDriver\Exception\ElementClickInterceptedException;
use Facebook\WebDriver\Exception\NoSuchElementException;
use Facebook\WebDriver\Exception\TimeoutException;
use Laravel\Dusk\Browser;
use Tests\Browser\Traits\HasFrontendActions;
use Tests\Browser\Traits\NavigatesFrontendDashboard;
use Tests\Browser\Traits\RollbackModelsTrait;
use Tests\DuskTestCase;
use Tests\Traits\MakesRequesterVoucherPayouts;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestIdentities;
use Tests\Traits\MakesTestReimbursements;
use Tests\Traits\MakesTestVouchers;
use Throwable;

class SponsorVoucherDirectPaymentsTest extends DuskTestCase
{
    use MakesTestFunds;
    use MakesTestIdentities;
    use MakesTestReimbursements;
    use MakesTestVouchers;
    use HasFrontendActions;
    use NavigatesFrontendDashboard;
    use RollbackModelsTrait;
    use MakesRequesterVoucherPayouts;

    /**
     * @throws Throwable
     * @return void
     */
    public function testSponsorCanCreateDirectPaymentWithProfileBankAccount(): void
    {
        $implementation = Implementation::byKey('nijmegen');
        $organization = $implementation->organization;
        $organizationState = $organization->only(['allow_profiles']);

        $organization->forceFill(['allow_profiles' => true])->save();

        $fund = $this->makeTestFund($organization, fundConfigsData: [
            'allow_direct_payments' => true,
        ], implementation: $implementation);

        $identity = $this->makeIdentity(
            $this->makeUniqueEmail(),
            type: Identity::TYPE_PROFILE,
            organizationId: $organization->id,
        );
        $profile = $organization->findOrMakeProfile($identity);
        $iban = $this->makeIban();
        $ibanName = $this->makeIbanName();
        $profile->profile_bank_accounts()->create([
            'iban' => $iban,
            'name' => $ibanName,
        ]);

        $voucher = $this->makeTestVoucher($fund, $identity);

        $this->rollbackModels([
            [$organization, $organizationState],
        ], function () use ($implementation, $organization, $voucher, $iban, $ibanName) {
            $this->browse(function (Browser $browser) use ($implementation, $organization, $voucher, $iban, $ibanName) {
                $transaction = $this->createDirectPayment(
                    $browser,
                    $implementation,
                    $organization,
                    $voucher,
                    2,
                    $iban,
                    $ibanName,
                );

                $this->assertNotNull($transaction);
                $this->assertEquals($iban, $transaction->target_iban);
                $this->assertEquals($ibanName, $transaction->target_name);
            });
        }, function () use ($fund) {
            $fund && $this->deleteFund($fund);
        });
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testSponsorCanCreateDirectPaymentWithReimbursement(): void
    {
        $implementation = Implementation::byKey('nijmegen');
        $organization = $implementation->organization;

        $fund = $this->makeTestFund($organization, fundConfigsData: [
            'allow_direct_payments' => true,
            'allow_reimbursements' => true,
        ], implementation: $implementation);

        $identity = $this->makeIdentity($this->makeUniqueEmail());
        $voucher = $this->makeTestVoucher($fund, $identity, amount: 100);
        $reimbursement = $this->makeReimbursement($voucher, submit: true);

        $this->rollbackModels([], function () use ($implementation, $organization, $voucher, $reimbursement) {
            $this->browse(function (Browser $browser) use ($implementation, $organization, $voucher, $reimbursement) {
                $transaction = $this->createDirectPayment(
                    $browser,
                    $implementation,
                    $organization,
                    $voucher,
                    3,
                    $reimbursement->iban,
                    $reimbursement->iban_name,
                );

                $this->assertNotNull($transaction);
                $this->assertEquals($reimbursement->iban, $transaction->target_iban);
                $this->assertEquals($reimbursement->iban_name, $transaction->target_name);
            });
        }, function () use ($fund) {
            $fund && $this->deleteFund($fund);
        });
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testSponsorCanCreateDirectPaymentWithFundRequestBankAccount(): void
    {
        $implementation = Implementation::byKey('nijmegen');
        $organization = $implementation->organization;

        $fund = $this->makePayoutEnabledFund($organization, implementation: $implementation, fundConfigsData: [
            'allow_direct_payments' => true,
        ]);

        $identity = $this->makeIdentity($this->makeUniqueEmail(), bsn: $this->randomFakeBsn());
        $result = $this->makePayoutVoucherViaApplication($identity, $fund);
        $fundRequest = $result['fund_request'];
        $voucher = $result['voucher'];

        $this->rollbackModels([], function () use ($implementation, $organization, $voucher, $fundRequest) {
            $this->browse(function (Browser $browser) use ($implementation, $organization, $voucher, $fundRequest) {
                $transaction = $this->createDirectPayment(
                    $browser,
                    $implementation,
                    $organization,
                    $voucher,
                    1,
                    $fundRequest->getIban(),
                    $fundRequest->getIbanName(),
                );

                $this->assertNotNull($transaction);
                $this->assertEquals($fundRequest->getIban(), $transaction->target_iban);
                $this->assertEquals($fundRequest->getIbanName(), $transaction->target_name);
            });
        }, function () use ($fund) {
            $fund && $this->deleteFund($fund);
        });
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testSponsorCanCreateDirectPaymentWithPayoutTransactionBankAccount(): void
    {
        $implementation = Implementation::byKey('nijmegen');
        $organization = $implementation->organization;
        $organization->forceFill(['allow_payouts' => true])->save();

        $fund = $this->makeTestFund($organization, fundConfigsData: [
            'allow_direct_payments' => true,
        ], implementation: $implementation);

        $identity = $this->makeIdentity($this->makeUniqueEmail());
        $voucher = $this->makeTestVoucher($fund, $identity);
        $previousPayoutTransaction = $voucher->makeTransaction([
            'initiator' => VoucherTransaction::INITIATOR_SPONSOR,
            'target' => VoucherTransaction::TARGET_PAYOUT,
            'target_iban' => $this->makeIban(),
            'target_name' => $this->makeIbanName(),
            'amount' => '50.00',
        ]);

        $this->rollbackModels([], function () use ($implementation, $organization, $voucher, $previousPayoutTransaction) {
            $this->browse(function (Browser $browser) use ($implementation, $organization, $voucher, $previousPayoutTransaction) {
                $transaction = $this->createDirectPayment(
                    $browser,
                    $implementation,
                    $organization,
                    $voucher,
                    3,
                    $previousPayoutTransaction->target_iban,
                    $previousPayoutTransaction->target_name,
                );

                $this->assertNotNull($transaction);
                $this->assertEquals($previousPayoutTransaction->target_iban, $transaction->target_iban);
                $this->assertEquals($previousPayoutTransaction->target_name, $transaction->target_name);
            });
        }, function () use ($fund) {
            $fund && $this->deleteFund($fund);
        });
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testSponsorCanCreateDirectPaymentWithManualIban(): void
    {
        $implementation = Implementation::byKey('nijmegen');
        $organization = $implementation->organization;

        $fund = $this->makeTestFund($organization, fundConfigsData: [
            'allow_direct_payments' => true,
        ], implementation: $implementation);

        $identity = $this->makeIdentity($this->makeUniqueEmail());
        $voucher = $this->makeTestVoucher($fund, $identity);
        $manualIban = $this->makeIban();
        $manualName = $this->makeIbanName();

        $this->rollbackModels([], function () use ($implementation, $organization, $voucher, $manualIban, $manualName) {
            $this->browse(function (Browser $browser) use ($implementation, $organization, $voucher, $manualIban, $manualName) {
                $transaction = $this->createDirectPayment(
                    $browser,
                    $implementation,
                    $organization,
                    $voucher,
                    0,
                    $manualIban,
                    $manualName,
                    manual: true,
                );

                $this->assertNotNull($transaction);
                $this->assertEquals($manualIban, $transaction->target_iban);
                $this->assertEquals($manualName, $transaction->target_name);
            });
        }, function () use ($fund) {
            $fund && $this->deleteFund($fund);
        });
    }

    /**
     * @throws NoSuchElementException
     * @throws TimeoutException
     * @throws ElementClickInterceptedException
     */
    protected function openVoucherTransactionModal(
        Browser $browser,
        Implementation $implementation,
        Organization $organization,
        Voucher $voucher
    ): Browser {
        $browser->visit($implementation->urlSponsorDashboard());

        $this->loginIdentity($browser, $organization->identity);
        $this->assertIdentityAuthenticatedOnSponsorDashboard($browser, $organization->identity);
        $this->selectDashboardOrganization($browser, $organization);

        $this->goToVouchersPage($browser);
        $this->searchTable($browser, '@tableVoucher', $voucher->identity->email, $voucher->id);

        $browser->click("@tableVoucherRow$voucher->id");
        $browser->waitFor('@voucherMakeTransaction');
        $browser->click('@voucherMakeTransaction');

        $browser->waitFor('@voucherTransactionModal');
        $this->changeSelectControl($browser, '@voucherTransactionTargetSelect', index: 1);

        return $browser;
    }

    /**
     * @throws TimeoutException
     * @throws NoSuchElementException
     * @throws ElementClickInterceptedException
     */
    protected function createDirectPayment(
        Browser $browser,
        Implementation $implementation,
        Organization $organization,
        Voucher $voucher,
        int $bankAccountSourceIndex,
        string $iban,
        string $name,
        bool $manual = false,
        int $bankAccountIndex = 1,
    ): VoucherTransaction {
        $browser = $this->openVoucherTransactionModal($browser, $implementation, $organization, $voucher);

        $this->changeSelectControl($browser, '@voucherTransactionBankAccountSourceSelect', index: $bankAccountSourceIndex);

        if ($manual) {
            $browser->assertEnabled('@voucherTransactionTargetIban');
            $browser->assertEnabled('@voucherTransactionTargetName');
            $browser->clear('@voucherTransactionTargetIban');
            $browser->clear('@voucherTransactionTargetName');
            $browser->type('@voucherTransactionTargetIban', $iban);
            $browser->type('@voucherTransactionTargetName', $name);
        } else {
            $this->changeSelectControl($browser, '@voucherTransactionBankAccountSelect', index: $bankAccountIndex);
            $browser->assertDisabled('@voucherTransactionTargetIban');
            $browser->assertDisabled('@voucherTransactionTargetName');
            $browser->assertInputValue('@voucherTransactionTargetIban', $iban);
            $browser->assertInputValue('@voucherTransactionTargetName', $name);
        }

        $browser->typeSlowly('@voucherTransactionAmount', '25.00', 20);
        $browser->click('@voucherTransactionSubmit');

        $browser->waitFor('@voucherTransactionPreviewSubmit');
        $browser->click('@voucherTransactionPreviewSubmit');

        $browser->waitFor('@voucherTransactionClose');
        $browser->click('@voucherTransactionClose');
        $browser->waitUntilMissing('@voucherTransactionModal');

        $this->logout($browser);

        return VoucherTransaction::where('voucher_id', $voucher->id)
            ->where('target', VoucherTransaction::TARGET_IBAN)
            ->latest('id')
            ->first();
    }
}
