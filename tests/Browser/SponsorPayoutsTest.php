<?php

namespace Tests\Browser;

use App\Models\Implementation;
use App\Models\Organization;
use App\Models\VoucherTransaction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Dusk\Browser;
use Tests\Browser\Traits\HasFrontendActions;
use Tests\Browser\Traits\NavigatesFrontendDashboard;
use Tests\Browser\Traits\RollbackModelsTrait;
use Tests\DuskTestCase;
use Tests\Traits\MakesRequesterVoucherPayouts;
use Tests\Traits\MakesTestFunds;
use Throwable;

class SponsorPayoutsTest extends DuskTestCase
{
    use WithFaker;
    use MakesTestFunds;
    use MakesRequesterVoucherPayouts;
    use HasFrontendActions;
    use NavigatesFrontendDashboard;
    use RollbackModelsTrait;

    /**
     * @throws Throwable
     * @return void
     */
    public function testSponsorPayoutsListShowsRequesterPayout(): void
    {
        $implementation = Implementation::byKey('nijmegen');
        $organization = $implementation->organization;
        $organizationState = $organization->only(['allow_profiles', 'fund_request_resolve_policy']);

        $organization->forceFill(['allow_profiles' => true])->save();
        $fund = $this->makePayoutEnabledFund($organization, $implementation);

        $requester = $this->makeIdentity($this->makeUniqueEmail(), bsn: $this->randomFakeBsn());
        $result = $this->makePayoutVoucherViaApplication($requester, $fund);

        $transaction = $this->apiMakePayout([
            'voucher_id' => $result['voucher']->id,
            'amount' => '50.00',
            'fund_request_id' => $result['fund_request']->id,
        ], $requester);

        $this->assertEquals(VoucherTransaction::INITIATOR_REQUESTER, $transaction->initiator);

        $this->rollbackModels([
            [$organization, $organizationState],
        ], function () use ($implementation, $organization, $transaction) {
            $this->browse(function (Browser $browser) use ($implementation, $organization, $transaction) {
                $browser->visit($implementation->urlSponsorDashboard());

                $this->loginIdentity($browser, $organization->identity);
                $this->assertIdentityAuthenticatedOnSponsorDashboard($browser, $organization->identity);
                $this->selectDashboardOrganization($browser, $organization);

                $this->goSponsorPayoutsPage($browser);

                $browser->waitFor('@payoutsPage');
                $browser->waitFor("@payoutsTableRow$transaction->id");
                $browser->assertPresent("@payoutsTableRow$transaction->id");

                $this->logout($browser);
            });
        }, function () use ($fund) {
            $fund && $this->deleteFund($fund);
        });
    }

    /**
     * @throws Throwable
     */
    public function testSponsorCanCreatePayoutManualBankAccount(): void
    {
        $implementation = Implementation::byKey('nijmegen');
        $organization = $implementation->organization;
        $organizationState = $organization->only(['allow_profiles', 'fund_request_resolve_policy']);

        $organization->forceFill(['allow_profiles' => true])->save();
        $payoutFund = $this->makeTestFund($organization, fundConfigsData: [
            'allow_custom_amounts' => true,
            'custom_amount_min' => 1,
            'custom_amount_max' => 1000,
        ], implementation: $implementation);

        $iban = $this->makeIban();
        $ibanName = $this->makeIbanName();

        $this->rollbackModels([
            [$organization, $organizationState],
        ], function () use ($implementation, $organization, $iban, $ibanName) {
            $this->browse(function (Browser $browser) use ($implementation, $organization, $iban, $ibanName) {
                $browser->visit($implementation->urlSponsorDashboard());

                $this->loginIdentity($browser, $organization->identity);
                $this->assertIdentityAuthenticatedOnSponsorDashboard($browser, $organization->identity);
                $this->selectDashboardOrganization($browser, $organization);

                $this->goSponsorPayoutsPage($browser);

                $browser->waitFor('@payoutsPage');
                $browser->waitFor('@payoutCreateButton');
                $browser->click('@payoutCreateButton');

                $browser->waitFor('@payoutCreateModal');
                $browser->waitFor('@payoutAmount');
                $browser->typeSlowly('@payoutAmount', '100.00', 20);
                $browser->type('@payoutTargetIban', $iban);
                $browser->type('@payoutTargetName', $ibanName);
                $browser->press('@payoutSubmit');
                $browser->waitUntilMissing('@payoutCreateModal');

                $transaction = $this->findTransactionByIban($organization, $iban);

                $this->assertNotNull($transaction);
                $this->assertEquals($ibanName, $transaction->target_name);

                $browser->waitFor("@payoutsTableRow$transaction->id");
                $browser->assertPresent("@payoutsTableRow$transaction->id");

                $this->logout($browser);
            });
        }, function () use ($payoutFund) {
            $payoutFund && $this->deleteFund($payoutFund);
        });
    }

    /**
     * @throws Throwable
     */
    public function testSponsorCanCreatePayoutFromFundRequestBankAccount(): void
    {
        $implementation = Implementation::byKey('nijmegen');
        $organization = $implementation->organization;
        $organizationState = $organization->only(['allow_profiles', 'fund_request_resolve_policy']);

        $organization->forceFill(['allow_profiles' => true])->save();
        $payoutFund = $this->makeTestFund($organization, fundConfigsData: [
            'allow_custom_amounts' => true,
            'custom_amount_min' => 1,
            'custom_amount_max' => 1000,
        ], implementation: $implementation);

        $requestFund = $this->makePayoutEnabledFund($organization, $implementation);
        $requester = $this->makeIdentity($this->makeUniqueEmail(), bsn: $this->randomFakeBsn());
        $result = $this->makePayoutVoucherViaApplication($requester, $requestFund);

        $this->rollbackModels([
            [$organization, $organizationState],
        ], function () use ($implementation, $organization, $result) {
            $this->browse(function (Browser $browser) use ($implementation, $organization, $result) {
                $browser->visit($implementation->urlSponsorDashboard());

                $this->loginIdentity($browser, $organization->identity);
                $this->assertIdentityAuthenticatedOnSponsorDashboard($browser, $organization->identity);
                $this->selectDashboardOrganization($browser, $organization);

                $this->goSponsorPayoutsPage($browser);

                $browser->waitFor('@payoutsPage');
                $browser->waitFor('@payoutCreateButton');
                $browser->click('@payoutCreateButton');

                $browser->waitFor('@payoutCreateModal');
                $this->changeSelectControl($browser, '@payoutBankAccountSourceSelect', index: 1);
                $this->changeSelectControl($browser, '@payoutFundRequestSelect', index: 1);

                $browser->waitFor('@payoutAmount');
                $browser->typeSlowly('@payoutAmount', '100.00', 20);
                $browser->press('@payoutSubmit');
                $browser->waitUntilMissing('@payoutCreateModal');

                $transaction = $this->findTransactionByIban($organization, $result['iban']);

                $this->assertNotNull($transaction);
                $this->assertEquals($result['iban_name'], $transaction->target_name);

                $browser->waitFor("@payoutsTableRow$transaction->id");
                $browser->assertPresent("@payoutsTableRow$transaction->id");

                $this->logout($browser);
            });
        }, function () use ($payoutFund, $requestFund) {
            $payoutFund && $this->deleteFund($payoutFund);
            $requestFund && $this->deleteFund($requestFund);
        });
    }

    /**
     * @param Organization $organization
     * @param string $iban
     * @return VoucherTransaction|null
     */
    private function findTransactionByIban(Organization $organization, string $iban): ?VoucherTransaction
    {
        return VoucherTransaction::query()
            ->whereHas('voucher.fund', fn (Builder $b) => $b->where('organization_id', $organization->id))
            ->where('target_iban', $iban)
            ->latest('id')
            ->first();
    }
}
