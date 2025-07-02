<?php

namespace Tests\Feature;

use App\Models\Voucher;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Tests\Traits\MakesTestFundProviders;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestOrganizations;
use Tests\Traits\MakesTestProducts;
use Tests\Traits\MakesTestVouchers;
use Throwable;

class VouchersProviderMeAppTest extends TestCase
{
    use WithFaker;
    use MakesTestFunds;
    use MakesTestVouchers;
    use MakesTestProducts;
    use DatabaseTransactions;
    use MakesTestOrganizations;
    use MakesTestFundProviders;

    /**
     * @throws Throwable
     * @return void
     */
    public function testVoucherAmountVisibility(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity($this->makeUniqueEmail()));
        $fund = $this->makeTestFund($organization);

        $fund->fund_config->update([
            'voucher_amount_visible' => false,
        ]);

        $voucher = $this->makeTestVoucher($fund, $this->makeIdentity(), [
            'state' => Voucher::STATE_ACTIVE,
        ], amount: 100);

        $this->assertNotNull($voucher);

        $providerIdentity = $this->makeIdentity($this->makeUniqueEmail());
        $provider = $this->makeTestProviderOrganization($providerIdentity);
        $fundProvider = $this->makeTestFundProvider($provider, $fund);

        $this->assertNotNull($fundProvider);

        $response = $this->getJson(
            "/api/v1/platform/provider/vouchers/{$voucher->token_with_confirmation->address}",
            $this->makeApiHeaders($this->makeIdentityProxy($providerIdentity)),
        );

        $response->assertSuccessful();
        $this->assertFalse($response->json('data.amount_visible'));
    }
}
