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
use Throwable;

class VouchersProviderMeAppTest extends TestCase
{
    use WithFaker;
    use MakesTestFunds;
    use MakesTestProducts;
    use DatabaseTransactions;
    use MakesTestOrganizations;
    use MakesTestFundProviders;

    /**
     * @return void
     * @throws Throwable
     */
    public function testVoucherAmountVisibility(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity($this->makeUniqueEmail()));
        $fund = $this->makeTestFund($organization);

        $fund->fund_config->update([
            'voucher_amount_visible' => false,
        ]);

        $voucher = $fund->makeVoucher($this->makeIdentity(), [
            'state' => Voucher::STATE_ACTIVE
        ], 100);

        $this->assertNotNull($voucher);

        $providerIdentity = $this->makeIdentity($this->makeUniqueEmail());
        $provider = $this->makeProviderOrganization($providerIdentity);
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
