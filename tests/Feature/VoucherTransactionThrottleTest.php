<?php

namespace Tests\Feature;

use App\Models\Role;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestOrganizations;
use Tests\Traits\MakesTestVouchers;

class VoucherTransactionThrottleTest extends TestCase
{
    use MakesTestFunds;
    use MakesTestVouchers;
    use DatabaseTransactions;
    use MakesTestOrganizations;

    public function testProviderVoucherTransactionIsThrottledByAttempts(): void
    {
        $initialHardLimit = Config::get('forus.transactions.hard_limit');
        Config::set('forus.transactions.hard_limit', 5);

        try {
            $sponsorIdentity = $this->makeIdentity($this->makeUniqueEmail('sponsor_'));
            $sponsorOrganization = $this->makeTestOrganization($sponsorIdentity);
            $fund = $this->makeTestFund($sponsorOrganization);

            $sponsorOrganization->addEmployee($sponsorIdentity, Role::pluck('id')->toArray());

            $providerIdentity = $this->makeIdentity($this->makeUniqueEmail('provider_'));
            $provider = $this->makeTestProviderOrganization($providerIdentity);

            $this->makeTestFundProvider($provider, $fund);

            $voucherIdentity = $this->makeIdentity($this->makeUniqueEmail('voucher_'));
            $voucher = $this->makeTestVoucher($fund, $voucherIdentity, amount: 100);

            $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.10'])
                ->makeProviderVoucherTransactionRequest($voucher, $provider, ['amount' => 1], $provider->identity)
                ->assertSuccessful();

            $response = $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.10'])
                ->makeProviderVoucherTransactionRequest($voucher, $provider, ['amount' => 1], $provider->identity)
                ->assertStatus(403);

            $meta = $response->json('meta');
            $this->assertEqualsWithDelta(5.0, $meta['decay_seconds'], 0.0001);
        } finally {
            Config::set('forus.transactions.hard_limit', $initialHardLimit);
        }
    }
}
