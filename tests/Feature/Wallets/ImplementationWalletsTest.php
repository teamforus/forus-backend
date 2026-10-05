<?php

namespace Tests\Feature\Wallets;

use App\Models\Identity;
use App\Models\Implementation;
use App\Models\Organization;
use App\Models\Role;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Config;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesWalletTestData;

class ImplementationWalletsTest extends TestCase
{
    use DatabaseTransactions;
    use MakesWalletTestData;
    use MakesTestFunds;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        Config::set('openid.enabled', true);
    }

    /**
     * @return void
     */
    public function testUpdateImplementationWalletsEnablesVeridForAllowedOrganization(): void
    {
        $implementation = $this->makeWalletImplementation([
            'openid_enabled' => false,
        ]);

        $response = $this->apiUpdateImplementationWalletsRequest($implementation, [
            'wallet_enabled' => true,
            'wallet_flow_keys' => [static::FAKE_FLOW_KEY],
        ], $implementation->organization->identity);

        $response
            ->assertSuccessful()
            ->assertJsonPath('data.wallet_enabled', true)
            ->assertJsonPath('data.wallet_configured', true)
            ->assertJsonPath('data.wallet_available', true);

        $this->assertSame([static::FAKE_FLOW_KEY], collect($response->json('data.wallet_flows'))->pluck('key')->all());
        $this->assertContains(
            static::FAKE_FLOW_KEY,
            collect($response->json('data.wallet_flow_options'))->pluck('key')->all(),
        );

        $this->assertArrayNotHasKey('enabled', $response->json('data.wallet_flows.0'));
        $this->assertTrue($implementation->refresh()->openid_enabled);
        $this->assertSame([static::FAKE_FLOW_KEY], $implementation->wallet_flows()->pluck('wallet_flows.key')->all());
    }

    /**
     * @return void
     */
    public function testUpdateImplementationWalletsDisablesVerid(): void
    {
        $implementation = $this->makeWalletImplementation();

        $response = $this->apiUpdateImplementationWalletsRequest($implementation, [
            'wallet_enabled' => false,
            'wallet_flow_keys' => [static::FAKE_FLOW_KEY],
        ], $implementation->organization->identity);

        $response
            ->assertSuccessful()
            ->assertJsonPath('data.wallet_enabled', false)
            ->assertJsonPath('data.wallet_configured', true)
            ->assertJsonPath('data.wallet_available', false);

        $implementation->refresh();

        $this->assertFalse($implementation->openid_enabled);
        $this->assertSame([static::FAKE_FLOW_KEY], $implementation->wallet_flows()->pluck('wallet_flows.key')->all());
    }

    /**
     * @return void
     */
    public function testUpdateImplementationWalletsCanEnableWhenNoFlowIsSelectedButProviderRemainsUnavailable(): void
    {
        $implementation = $this->makeWalletImplementation([
            'openid_enabled' => false,
        ], walletFlow: $this->makeWalletFlow(['key' => 'datakeeper']));

        $response = $this->apiUpdateImplementationWalletsRequest($implementation, [
            'wallet_enabled' => true,
            'wallet_flow_keys' => [],
        ], $implementation->organization->identity);

        $response
            ->assertSuccessful()
            ->assertJsonPath('data.wallet_enabled', true)
            ->assertJsonPath('data.wallet_configured', true)
            ->assertJsonPath('data.wallet_available', false);

        $this->assertTrue($implementation->refresh()->openid_enabled);
        $this->assertSame([], $implementation->wallet_flows()->pluck('wallet_flows.key')->all());
    }

    /**
     * @return void
     */
    public function testUpdateImplementationWalletsRejectsMissingEnabledFlag(): void
    {
        $implementation = $this->makeWalletImplementation([
            'openid_enabled' => true,
        ]);

        $this
            ->apiUpdateImplementationWalletsRequest($implementation, [
                'wallet_flow_keys' => [static::FAKE_FLOW_KEY],
            ], $implementation->organization->identity)
            ->assertJsonValidationErrors(['wallet_enabled']);

        $this->assertTrue($implementation->refresh()->openid_enabled);
    }

    /**
     * @return void
     */
    public function testUpdateImplementationWalletsRejectsInvalidEnabledFlag(): void
    {
        $implementation = $this->makeWalletImplementation([
            'openid_enabled' => true,
        ]);

        $this
            ->apiUpdateImplementationWalletsRequest($implementation, [
                'wallet_enabled' => 'not-a-boolean',
                'wallet_flow_keys' => [static::FAKE_FLOW_KEY],
            ], $implementation->organization->identity)
            ->assertJsonValidationErrors(['wallet_enabled']);

        $this->assertTrue($implementation->refresh()->openid_enabled);
    }

    /**
     * @return void
     */
    public function testUpdateImplementationWalletsRejectsWhenOrganizationDoesNotAllowWallet(): void
    {
        $implementation = $this->makeWalletImplementation([
            'openid_enabled' => false,
        ], [
            'allow_openid' => false,
        ]);

        $this
            ->apiUpdateImplementationWalletsRequest($implementation, [
                'wallet_enabled' => true,
                'wallet_flow_keys' => [static::FAKE_FLOW_KEY],
            ], $implementation->organization->identity)
            ->assertForbidden();

        $this->assertFalse($implementation->refresh()->openid_enabled);
    }

    /**
     * @return void
     */
    public function testUpdateImplementationWalletsRejectsNonEmployee(): void
    {
        $implementation = $this->makeWalletImplementation([
            'openid_enabled' => false,
        ]);

        $this
            ->apiUpdateImplementationWalletsRequest($implementation, [
                'wallet_enabled' => true,
                'wallet_flow_keys' => [static::FAKE_FLOW_KEY],
            ], $this->makeIdentity())
            ->assertForbidden();

        $this->assertFalse($implementation->refresh()->openid_enabled);
    }

    /**
     * @return void
     */
    public function testUpdateImplementationWalletsRejectsEmployeeWithoutManageImplementationPermission(): void
    {
        $employeeIdentity = $this->makeIdentity();

        $implementation = $this->makeWalletImplementation([
            'openid_enabled' => false,
        ]);

        $implementation->organization->addEmployee($employeeIdentity, [
            Role::where('key', 'finance')->firstOrFail()->id,
        ]);

        $this
            ->apiUpdateImplementationWalletsRequest($implementation, [
                'wallet_enabled' => true,
                'wallet_flow_keys' => [static::FAKE_FLOW_KEY],
            ], $employeeIdentity)
            ->assertForbidden();

        $this->assertFalse($implementation->refresh()->openid_enabled);
    }

    /**
     * @return void
     */
    public function testUpdateImplementationWalletsRejectsImplementationFromDifferentOrganization(): void
    {
        $routeImplementation = $this->makeWalletImplementation();
        $implementation = $this->makeWalletImplementation([
            'openid_enabled' => false,
        ]);

        $this
            ->apiUpdateImplementationWalletsForOrganizationRequest(
                $routeImplementation->organization,
                $implementation,
                ['wallet_enabled' => true, 'wallet_flow_keys' => [static::FAKE_FLOW_KEY]],
                $routeImplementation->organization->identity,
            )
            ->assertForbidden();

        $this->assertFalse($implementation->refresh()->openid_enabled);
    }

    /**
     * @param Organization $organization
     * @param Implementation $implementation
     * @param array $data
     * @param Identity $identity
     * @return TestResponse
     */
    protected function apiUpdateImplementationWalletsForOrganizationRequest(
        Organization $organization,
        Implementation $implementation,
        array $data,
        Identity $identity,
    ): TestResponse {
        return $this->patchJson(
            sprintf(
                '/api/v1/platform/organizations/%s/implementations/%s/wallets',
                $organization->id,
                $implementation->id,
            ),
            $data,
            $this->makeApiHeaders($identity),
        );
    }
}
