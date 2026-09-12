<?php

namespace Tests\Feature\OpenId;

use App\Models\Identity;
use App\Models\Implementation;
use App\Models\Organization;
use App\Models\Role;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Config;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;
use Tests\Traits\MakesOpenIdTestData;
use Tests\Traits\MakesTestFunds;

class ImplementationOpenIdTest extends TestCase
{
    use DatabaseTransactions;
    use MakesOpenIdTestData;
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
    public function testUpdateImplementationOpenIdEnablesVeridForAllowedOrganization(): void
    {
        $implementation = $this->makeOpenIdImplementation([
            'openid_enabled' => false,
        ]);

        $response = $this->apiUpdateImplementationOpenIdRequest($implementation, [
            'openid_enabled' => true,
            'openid_flow_keys' => [static::FAKE_FLOW_KEY],
        ], $implementation->organization->identity);

        $response
            ->assertSuccessful()
            ->assertJsonPath('data.openid_enabled', true)
            ->assertJsonPath('data.openid_configured', true)
            ->assertJsonPath('data.openid_available', true);

        $this->assertSame([static::FAKE_FLOW_KEY], collect($response->json('data.openid_flows'))->pluck('key')->all());
        $this->assertContains(
            static::FAKE_FLOW_KEY,
            collect($response->json('data.openid_flow_options'))->pluck('key')->all(),
        );

        $this->assertArrayNotHasKey('enabled', $response->json('data.openid_flows.0'));
        $this->assertTrue($implementation->refresh()->openid_enabled);
        $this->assertSame([static::FAKE_FLOW_KEY], $implementation->openid_flows()->pluck('openid_flows.key')->all());
    }

    /**
     * @return void
     */
    public function testUpdateImplementationOpenIdDisablesVerid(): void
    {
        $implementation = $this->makeOpenIdImplementation();

        $response = $this->apiUpdateImplementationOpenIdRequest($implementation, [
            'openid_enabled' => false,
            'openid_flow_keys' => [static::FAKE_FLOW_KEY],
        ], $implementation->organization->identity);

        $response
            ->assertSuccessful()
            ->assertJsonPath('data.openid_enabled', false)
            ->assertJsonPath('data.openid_configured', true)
            ->assertJsonPath('data.openid_available', false);

        $implementation->refresh();

        $this->assertFalse($implementation->openid_enabled);
        $this->assertSame([static::FAKE_FLOW_KEY], $implementation->openid_flows()->pluck('openid_flows.key')->all());
    }

    /**
     * @return void
     */
    public function testUpdateImplementationOpenIdCanEnableWhenNoFlowIsSelectedButProviderRemainsUnavailable(): void
    {
        $implementation = $this->makeOpenIdImplementation([
            'openid_enabled' => false,
        ], openidFlow: $this->makeOpenIdFlow(['key' => 'datakeeper', 'context' => null]));

        $response = $this->apiUpdateImplementationOpenIdRequest($implementation, [
            'openid_enabled' => true,
            'openid_flow_keys' => [],
        ], $implementation->organization->identity);

        $response
            ->assertSuccessful()
            ->assertJsonPath('data.openid_enabled', true)
            ->assertJsonPath('data.openid_configured', true)
            ->assertJsonPath('data.openid_available', false);

        $this->assertTrue($implementation->refresh()->openid_enabled);
        $this->assertSame([], $implementation->openid_flows()->pluck('openid_flows.key')->all());
    }

    /**
     * @return void
     */
    public function testUpdateImplementationOpenIdRejectsMissingEnabledFlag(): void
    {
        $implementation = $this->makeOpenIdImplementation([
            'openid_enabled' => true,
        ]);

        $this
            ->apiUpdateImplementationOpenIdRequest($implementation, [
                'openid_flow_keys' => [static::FAKE_FLOW_KEY],
            ], $implementation->organization->identity)
            ->assertJsonValidationErrors(['openid_enabled']);

        $this->assertTrue($implementation->refresh()->openid_enabled);
    }

    /**
     * @return void
     */
    public function testUpdateImplementationOpenIdRejectsInvalidEnabledFlag(): void
    {
        $implementation = $this->makeOpenIdImplementation([
            'openid_enabled' => true,
        ]);

        $this
            ->apiUpdateImplementationOpenIdRequest($implementation, [
                'openid_enabled' => 'not-a-boolean',
                'openid_flow_keys' => [static::FAKE_FLOW_KEY],
            ], $implementation->organization->identity)
            ->assertJsonValidationErrors(['openid_enabled']);

        $this->assertTrue($implementation->refresh()->openid_enabled);
    }

    /**
     * @return void
     */
    public function testUpdateImplementationOpenIdRejectsWhenOrganizationDoesNotAllowOpenId(): void
    {
        $implementation = $this->makeOpenIdImplementation([
            'openid_enabled' => false,
        ], [
            'allow_openid' => false,
        ]);

        $this
            ->apiUpdateImplementationOpenIdRequest($implementation, [
                'openid_enabled' => true,
                'openid_flow_keys' => [static::FAKE_FLOW_KEY],
            ], $implementation->organization->identity)
            ->assertForbidden();

        $this->assertFalse($implementation->refresh()->openid_enabled);
    }

    /**
     * @return void
     */
    public function testUpdateImplementationOpenIdRejectsNonEmployee(): void
    {
        $implementation = $this->makeOpenIdImplementation([
            'openid_enabled' => false,
        ]);

        $this
            ->apiUpdateImplementationOpenIdRequest($implementation, [
                'openid_enabled' => true,
                'openid_flow_keys' => [static::FAKE_FLOW_KEY],
            ], $this->makeIdentity())
            ->assertForbidden();

        $this->assertFalse($implementation->refresh()->openid_enabled);
    }

    /**
     * @return void
     */
    public function testUpdateImplementationOpenIdRejectsEmployeeWithoutManageImplementationPermission(): void
    {
        $employeeIdentity = $this->makeIdentity();

        $implementation = $this->makeOpenIdImplementation([
            'openid_enabled' => false,
        ]);

        $implementation->organization->addEmployee($employeeIdentity, [
            Role::where('key', 'finance')->firstOrFail()->id,
        ]);

        $this
            ->apiUpdateImplementationOpenIdRequest($implementation, [
                'openid_enabled' => true,
                'openid_flow_keys' => [static::FAKE_FLOW_KEY],
            ], $employeeIdentity)
            ->assertForbidden();

        $this->assertFalse($implementation->refresh()->openid_enabled);
    }

    /**
     * @return void
     */
    public function testUpdateImplementationOpenIdRejectsImplementationFromDifferentOrganization(): void
    {
        $routeImplementation = $this->makeOpenIdImplementation();
        $implementation = $this->makeOpenIdImplementation([
            'openid_enabled' => false,
        ]);

        $this
            ->apiUpdateImplementationOpenIdForOrganizationRequest(
                $routeImplementation->organization,
                $implementation,
                ['openid_enabled' => true, 'openid_flow_keys' => [static::FAKE_FLOW_KEY]],
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
    protected function apiUpdateImplementationOpenIdForOrganizationRequest(
        Organization $organization,
        Implementation $implementation,
        array $data,
        Identity $identity,
    ): TestResponse {
        return $this->patchJson(
            sprintf(
                '/api/v1/platform/organizations/%s/implementations/%s/openid',
                $organization->id,
                $implementation->id,
            ),
            $data,
            $this->makeApiHeaders($identity),
        );
    }
}
