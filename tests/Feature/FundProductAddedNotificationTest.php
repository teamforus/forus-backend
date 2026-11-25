<?php

namespace Tests\Feature;

use App\Events\Products\ProductCreated;
use App\Models\Employee;
use App\Models\FundProvider;
use App\Models\Organization;
use App\Models\Permission;
use App\Models\Product;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tests\Traits\MakesTestFunds;

class FundProductAddedNotificationTest extends TestCase
{
    use DatabaseTransactions;
    use MakesTestFunds;

    /**
     * @return void
     */
    public function testSponsorOwnedProductNotifiesOnlyItsSponsor(): void
    {
        $provider = $this->makeTestProviderOrganization($this->makeIdentity());
        [$fundProviderA, $employeeA] = $this->makeSponsorRecipient($provider);
        [, $employeeB] = $this->makeSponsorRecipient($provider);

        $product = $this->makeTestProduct($provider, attributes: [
            'sponsor_organization_id' => $employeeA->organization_id,
        ]);

        ProductCreated::dispatch($product);

        $this->assertProductNotification($employeeA, $fundProviderA, $product);
        $this->assertCount(0, $this->productAddedNotifications($employeeB));
    }

    /**
     * @return void
     */
    public function testProviderOwnedProductNotifiesEachSponsorWithItsOwnRoute(): void
    {
        $provider = $this->makeTestProviderOrganization($this->makeIdentity());
        [$fundProviderA, $employeeA] = $this->makeSponsorRecipient($provider);
        [$fundProviderB, $employeeB] = $this->makeSponsorRecipient($provider);
        $product = $this->makeTestProduct($provider);

        ProductCreated::dispatch($product);

        $this->assertProductNotification($employeeA, $fundProviderA, $product);
        $this->assertProductNotification($employeeB, $fundProviderB, $product);
    }

    /**
     * @param Organization $provider
     * @return array{FundProvider, Employee}
     */
    private function makeSponsorRecipient(Organization $provider): array
    {
        $sponsor = $this->makeTestOrganization($this->makeIdentity());

        return [
            $this->makeTestFundProvider($provider, $this->makeTestFund($sponsor)),
            $this->makeTestEmployeeWithPermissions($sponsor, [Permission::MANAGE_PROVIDERS]),
        ];
    }

    /**
     * @param Employee $employee
     * @return array
     */
    private function productAddedNotifications(Employee $employee): array
    {
        $query = ['organization_id' => $employee->organization_id];
        $response = $this->apiGetNotificationsRequest($employee->identity, 'sponsor', $query)->assertOk();

        return $response->collect('data')->where('type', 'notifications_funds.product_added')->values()->all();
    }

    /**
     * @param Employee $employee
     * @param FundProvider $fundProvider
     * @param Product $product
     * @return void
     */
    private function assertProductNotification(Employee $employee, FundProvider $fundProvider, Product $product): void
    {
        $notifications = $this->productAddedNotifications($employee);
        $this->assertCount(1, $notifications);

        $route = $notifications[0]['route'];
        $this->assertSame('fund-provider-product', $route['name']);

        $this->assertEquals([
            'organizationId' => $employee->organization_id,
            'fundId' => $fundProvider->fund_id,
            'fundProviderId' => $fundProvider->id,
            'id' => $product->id,
        ], $route['params']);
    }
}
