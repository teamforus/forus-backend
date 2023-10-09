<?php

namespace Tests\Feature;

use App\Helpers\Arr;
use App\Models\Fund;
use App\Models\FundConfig;
use App\Models\Organization;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Tests\Traits\MakesTestIdentities;

class Organization2FATest extends TestCase
{
    use DatabaseTransactions, WithFaker, MakesTestIdentities;

    /**
     * @return void
     */
    public function testUpdateGlobal2FAFundSettings(): void
    {
        $this->assertOrganization2FAUpdate([
            'auth_2fa_funds_policy' => Organization::AUTH_2FA_FUNDS_POLICY_RESTRICT,
            'auth_2fa_funds_restrict_emails' => true,
            'auth_2fa_funds_restrict_auth_sessions' => true,
            'auth_2fa_funds_restrict_reimbursements' => true,
        ]);

        $this->assertOrganization2FAUpdate([
            'auth_2fa_funds_policy' => Organization::AUTH_2FA_FUNDS_POLICY_REQUIRED,
            'auth_2fa_funds_remember_ip' => true,
        ]);
    }

    /**
     * @return void
     */
    public function testUpdate2FAFundSettings(): void
    {
        $this->assertFund2FAUpdate([
            'auth_2fa_policy' => FundConfig::AUTH_2FA_POLICY_GLOBAL,
        ]);
    }

    /**
     * @return void
     */
    public function test2FAGlobalFundSettings(): void
    {
        $organization = $this->getOrganization();
        $headers = $this->makeApiHeaders($this->makeIdentityProxy($organization->identity));

        /** @var Fund $fund */
        $fund = $organization->funds->first();
        $fund->makeVoucher($organization->identity->address);

        $fund->fund_config->update([
            'auth_2fa_policy' => FundConfig::AUTH_2FA_POLICY_GLOBAL,
        ]);

        $organization->update([
            'auth_2fa_funds_policy' => Organization::AUTH_2FA_FUNDS_POLICY_REQUIRED,
            'auth_2fa_funds_remember_ip' => false,
        ]);

        $this->getJson('/api/v1/identity/2fa', $headers)->assertJsonPath('data.auth_2fa_forget_force.voucher', true);

        $organization->update([
            'auth_2fa_funds_policy' => Organization::AUTH_2FA_FUNDS_POLICY_REQUIRED,
            'auth_2fa_funds_remember_ip' => true,
        ]);

        $this->getJson('/api/v1/identity/2fa', $headers)->assertJsonPath('data.auth_2fa_forget_force.voucher', false);

        $organization->update([
            'auth_2fa_funds_policy' => Organization::AUTH_2FA_FUNDS_POLICY_RESTRICT,
            'auth_2fa_funds_restrict_emails' => true,
            'auth_2fa_funds_restrict_auth_sessions' => true,
            'auth_2fa_funds_restrict_reimbursements' => true,
        ]);

        $restrictions = $this->getJson('/api/v1/identity/2fa', $headers)->json('data.restrictions');

        $this->assertNotEmpty(Arr::first(Arr::get($restrictions, 'emails.funds'), fn ($item) => $item['id'] == $fund->id));
        $this->assertNotEmpty(Arr::first(Arr::get($restrictions, 'sessions.funds'), fn ($item) => $item['id'] == $fund->id));
        $this->assertNotEmpty(Arr::first(Arr::get($restrictions, 'reimbursements.funds'), fn ($item) => $item['id'] == $fund->id));

        $organization->update([
            'auth_2fa_funds_policy' => Organization::AUTH_2FA_FUNDS_POLICY_RESTRICT,
            'auth_2fa_funds_restrict_emails' => false,
            'auth_2fa_funds_restrict_auth_sessions' => false,
            'auth_2fa_funds_restrict_reimbursements' => false,
        ]);

        $restrictions = $this->getJson('/api/v1/identity/2fa', $headers)->json('data.restrictions');

        $this->assertEmpty(Arr::first(Arr::get($restrictions, 'emails.funds'), fn ($item) => $item['id'] == $fund->id));
        $this->assertEmpty(Arr::first(Arr::get($restrictions, 'sessions.funds'), fn ($item) => $item['id'] == $fund->id));
        $this->assertEmpty(Arr::first(Arr::get($restrictions, 'reimbursements.funds'), fn ($item) => $item['id'] == $fund->id));

    }

    /**
     * @param $data
     * @return void
     */
    protected function assertOrganization2FAUpdate($data): void
    {
        $organization = $this->getOrganization();
        $identityProxy = $this->makeIdentityProxy($organization->identity);

        $apiHeaders = $this->makeApiHeaders($identityProxy, [
            'client_type' => 'sponsor',
        ]);
        $response = $this->patchJson('/api/v1/platform/organizations/'. $organization->id, $data, $apiHeaders);

        $response->assertSuccessful();
        $organization = Organization::find($response->json('data.id'));

        foreach ($data as $fieldKey => $fieldValue) {
            $this->assertEquals($fieldValue, $organization->$fieldKey);
        }
    }

    /**
     * @param $data
     * @return void
     */
    protected function assertFund2FAUpdate($data): void
    {
        $organization = $this->getOrganization();
        $identityProxy = $this->makeIdentityProxy($organization->identity);
        /** @var Fund $fund */
        $fund = $organization->funds->first();

        $apiHeaders = $this->makeApiHeaders($identityProxy, [
            'client_type' => 'sponsor',
        ]);
        $response = $this->patchJson("/api/v1/platform/organizations/$organization->id/funds/$fund->id", $data, $apiHeaders);

        $response->assertSuccessful();
        $fund = Fund::find($response->json('data.id'));
        foreach ($data as $fieldKey => $fieldValue) {
            $this->assertEquals($fieldValue, $fund->fund_config->$fieldKey);
        }
    }

    /**
     * @return Organization
     */
    private function getOrganization(): Organization
    {
        return Organization::whereHas('funds')->where('allow_2fa_restrictions', true)->first();
    }
}
