<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tests\Traits\MakesRequesterVoucherPayouts;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestOrganizations;
use Throwable;

class RequesterVoucherPayoutButtonsTest extends TestCase
{
    use DatabaseTransactions;
    use MakesRequesterVoucherPayouts;
    use MakesTestFunds;
    use MakesTestOrganizations;

    /**
     * @throws Throwable
     */
    public function testVoucherShowResourceDefaultsVoucherPayoutButtonsToAllWhenConfigIsNull(): void
    {
        $this->assertVoucherShowResourceVoucherPayoutButtons(null, [
            'vouchers' => true,
            'payouts' => true,
            'products' => true,
        ]);
    }

    /**
     * @throws Throwable
     */
    public function testVoucherShowResourceDisablesAllVoucherPayoutButtonsWhenConfigIsEmpty(): void
    {
        $this->assertVoucherShowResourceVoucherPayoutButtons('', [
            'vouchers' => false,
            'payouts' => false,
            'products' => false,
        ]);
    }

    /**
     * @throws Throwable
     */
    public function testVoucherShowResourceParsesVoucherPayoutButtonsCsv(): void
    {
        $this->assertVoucherShowResourceVoucherPayoutButtons('vouchers,products', [
            'vouchers' => true,
            'payouts' => false,
            'products' => true,
        ]);
    }

    /**
     * @throws Throwable
     */
    public function testVoucherShowResourceIgnoresWhitespaceDuplicatesAndUnknownVoucherPayoutButtons(): void
    {
        $this->assertVoucherShowResourceVoucherPayoutButtons(' vouchers, payouts , vouchers, unknown ', [
            'vouchers' => true,
            'payouts' => true,
            'products' => false,
        ]);
    }

    /**
     * @param string|null $allowVoucherPayoutButtons
     * @param array $expected
     * @throws Throwable
     * @return void
     */
    protected function assertVoucherShowResourceVoucherPayoutButtons(?string $allowVoucherPayoutButtons, array $expected): void
    {
        $requester = $this->makeIdentity($this->makeUniqueEmail(), bsn: $this->randomFakeBsn());
        $sponsorOrganization = $this->makeTestOrganization($this->makeIdentity());
        $sponsorOrganization->forceFill(['allow_profiles' => true])->save();
        $fund = $this->makePayoutEnabledFund($sponsorOrganization);
        $result = $this->makePayoutVoucherViaApplication($requester, $fund);
        $voucher = $result['voucher'];

        $fund->fund_config->forceFill([
            'allow_voucher_payout_buttons' => $allowVoucherPayoutButtons,
        ])->save();

        $response = $this->getJson("/api/v1/platform/vouchers/$voucher->number", $this->makeApiHeaders($requester));
        $response->assertSuccessful();

        $this->assertSame($expected, $response->json('data.fund.allow_voucher_payout_buttons'));
    }
}
