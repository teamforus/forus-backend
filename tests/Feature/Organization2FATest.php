<?php

namespace Tests\Feature;

use App\Helpers\Arr;
use App\Models\Fund;
use App\Models\FundConfig;
use App\Models\Organization;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestIdentities;
use Tests\Traits\MakesTestOrganizations;

class Organization2FATest extends TestCase
{
    use DatabaseTransactions, WithFaker, MakesTestIdentities, MakesTestOrganizations, MakesTestFunds;

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
    public function testUpdateGlobal2FASettings(): void
    {
        $this->assertOrganization2FAUpdate([
            'auth_2fa_policy' => Organization::AUTH_2FA_POLICY_OPTIONAL,
            'auth_2fa_remember_ip' => false,
        ]);

        $this->assertOrganization2FAUpdate([
            'auth_2fa_policy' => Organization::AUTH_2FA_POLICY_REQUIRED,
            'auth_2fa_remember_ip' => true,
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

        // other funds without restriction
        $unrestrictedFunds = $organization->funds
            ->filter(fn (Fund $item) => $item->id !== $fund->id)
            ->each(fn (Fund $fund) => $fund->fund_config->forceFill([
                'auth_2fa_policy' => FundConfig::AUTH_2FA_POLICY_RESTRICT,
                'auth_2fa_restrict_emails' => false,
                'auth_2fa_restrict_auth_sessions' => false,
                'auth_2fa_restrict_reimbursements' => false,
            ])->save())
            ->pluck('id')
            ->toArray();

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
        $restrictedEmails = Arr::get($restrictions, 'emails.funds');
        $restrictedSessions = Arr::get($restrictions, 'sessions.funds');
        $restrictedReimbursements = Arr::get($restrictions, 'reimbursements.funds');

        $this->assertNotEmpty(Arr::first($restrictedEmails, fn ($item) => $item['id'] === $fund->id));
        $this->assertNotEmpty(Arr::first($restrictedSessions, fn ($item) => $item['id'] === $fund->id));
        $this->assertNotEmpty(Arr::first($restrictedReimbursements, fn ($item) => $item['id'] === $fund->id));

        $this->assertEmpty(Arr::first($restrictedEmails, fn ($item) => in_array($item['id'], $unrestrictedFunds, true)));
        $this->assertEmpty(Arr::first($restrictedSessions, fn ($item) => in_array($item['id'], $unrestrictedFunds, true)));
        $this->assertEmpty(Arr::first($restrictedReimbursements, fn ($item) => in_array($item['id'], $unrestrictedFunds, true)));

        $organization->update([
            'auth_2fa_funds_policy' => Organization::AUTH_2FA_FUNDS_POLICY_RESTRICT,
            'auth_2fa_funds_restrict_emails' => false,
            'auth_2fa_funds_restrict_auth_sessions' => false,
            'auth_2fa_funds_restrict_reimbursements' => false,
        ]);

        $restrictions = $this->getJson('/api/v1/identity/2fa', $headers)->json('data.restrictions');
        $restrictedEmails = Arr::get($restrictions, 'emails.funds');
        $restrictedSessions = Arr::get($restrictions, 'sessions.funds');
        $restrictedReimbursements = Arr::get($restrictions, 'reimbursements.funds');

        $this->assertEmpty(Arr::first($restrictedEmails, fn ($item) => $item['id'] === $fund->id));
        $this->assertEmpty(Arr::first($restrictedSessions, fn ($item) => $item['id'] === $fund->id));
        $this->assertEmpty(Arr::first($restrictedReimbursements, fn ($item) => $item['id'] === $fund->id));

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
        $organization = $this->makeTestOrganization($this->makeIdentity($this->makeUniqueEmail()), [
            'allow_2fa_restrictions' => true,
        ]);

        $this->makeTestFund($organization);
        $this->makeTestFund($organization);
        $this->makeTestFund($organization);

        return $organization->refresh();
    }
}
