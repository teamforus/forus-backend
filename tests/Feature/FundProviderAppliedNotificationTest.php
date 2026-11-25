<?php

namespace Tests\Feature;

use App\Models\Fund;
use App\Models\Permission;
use App\Notifications\Organizations\Funds\FundProviderAppliedNotification;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tests\Traits\MakesTestFunds;

class FundProviderAppliedNotificationTest extends TestCase
{
    use DatabaseTransactions;
    use MakesTestFunds;

    /**
     * @return void
     */
    public function testProviderApplicationLinksSponsorToFundProvider(): void
    {
        $provider = $this->makeTestProviderOrganization($this->makeIdentity());
        $sponsor = $this->makeTestOrganization($this->makeIdentity());
        $employee = $this->makeTestEmployeeWithPermissions($sponsor, [Permission::MANAGE_PROVIDERS]);

        $funds = [
            $this->makeTestFund($sponsor, fundConfigsData: ['allow_provider_sign_up' => true]),
            $this->makeTestFund($sponsor, fundConfigsData: ['allow_provider_sign_up' => true]),
        ];

        $fundProviders = [];

        foreach ($funds as $fund) {
            $fundProviders[] = $this->apiApplyProviderToFund($provider, $fund, $provider->identity);
        }

        $query = ['organization_id' => $sponsor->id];
        $response = $this->apiGetNotificationsRequest($employee->identity, 'sponsor', $query)->assertOk();
        $applications = $response->collect('data')->where('type', 'notifications_funds.provider_applied');

        $this->assertCount(2, $applications);

        foreach ($fundProviders as $fundProvider) {
            $route = $applications->sole('route.params.fundId', $fundProvider->fund_id)['route'];
            $this->assertSame('fund-provider', $route['name']);

            $this->assertEquals([
                'organizationId' => $sponsor->id,
                'fundId' => $fundProvider->fund_id,
                'id' => $fundProvider->id,
            ], $route['params']);
        }
    }

    /**
     * @return void
     */
    public function testSponsorCanReadOlderProviderApplicationNotificationWithoutRoute(): void
    {
        $sponsor = $this->makeTestOrganization($this->makeIdentity());
        $employee = $this->makeTestEmployeeWithPermissions($sponsor, [Permission::MANAGE_PROVIDERS]);
        $fund = $this->makeTestFund($sponsor);
        $provider = $this->makeTestProviderOrganization($this->makeIdentity());

        $event = $fund->log(Fund::EVENT_PROVIDER_APPLIED, [
            'fund' => $fund,
            'sponsor' => $sponsor,
            'fund_config' => $fund->fund_config,
            'implementation' => $fund->getImplementation(),
            'provider' => $provider,
        ]);

        FundProviderAppliedNotification::send($event);

        $query = ['organization_id' => $sponsor->id];
        $response = $this->apiGetNotificationsRequest($employee->identity, 'sponsor', $query)->assertOk();
        $notification = $response->collect('data')->sole('type', 'notifications_funds.provider_applied');

        $this->assertNotEmpty($notification['title']);
        $this->assertNull($notification['route']);
    }
}
