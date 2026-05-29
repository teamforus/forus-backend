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
            'openid_verid_enabled' => false,
        ]);

        $response = $this->apiUpdateImplementationOpenIdRequest($implementation, [
            'openid_verid_enabled' => true,
        ], $implementation->organization->identity);

        $response
            ->assertSuccessful()
            ->assertJsonPath('data.openid_verid_enabled', true)
            ->assertJsonPath('data.openid_verid_configured', true)
            ->assertJsonPath('data.openid_available', true);

        $this->assertTrue($implementation->refresh()->openid_verid_enabled);
    }

    /**
     * @return void
     */
    public function testUpdateImplementationOpenIdDisablesVerid(): void
    {
        $implementation = $this->makeOpenIdImplementation();
        $context = $implementation->openid_verid_context;

        $response = $this->apiUpdateImplementationOpenIdRequest($implementation, [
            'openid_verid_enabled' => false,
        ], $implementation->organization->identity);

        $response
            ->assertSuccessful()
            ->assertJsonPath('data.openid_verid_enabled', false)
            ->assertJsonPath('data.openid_verid_configured', true)
            ->assertJsonPath('data.openid_available', false);

        $implementation->refresh();

        $this->assertFalse($implementation->openid_verid_enabled);
        $this->assertEquals($context, $implementation->openid_verid_context);
    }

    /**
     * @return void
     */
    public function testUpdateImplementationOpenIdCanEnableWhenContextIsMissingButProviderRemainsUnavailable(): void
    {
        $implementation = $this->makeOpenIdImplementation([
            'openid_verid_enabled' => false,
            'openid_verid_context' => null,
        ]);

        $response = $this->apiUpdateImplementationOpenIdRequest($implementation, [
            'openid_verid_enabled' => true,
        ], $implementation->organization->identity);

        $response
            ->assertSuccessful()
            ->assertJsonPath('data.openid_verid_enabled', true)
            ->assertJsonPath('data.openid_verid_configured', false)
            ->assertJsonPath('data.openid_available', false);

        $this->assertTrue($implementation->refresh()->openid_verid_enabled);
        $this->assertNull($implementation->openid_verid_context);
    }

    /**
     * @return void
     */
    public function testUpdateImplementationOpenIdRejectsMissingEnabledFlag(): void
    {
        $implementation = $this->makeOpenIdImplementation([
            'openid_verid_enabled' => true,
        ]);

        $this
            ->apiUpdateImplementationOpenIdRequest($implementation, [], $implementation->organization->identity)
            ->assertJsonValidationErrors(['openid_verid_enabled']);

        $this->assertTrue($implementation->refresh()->openid_verid_enabled);
    }

    /**
     * @return void
     */
    public function testUpdateImplementationOpenIdRejectsInvalidEnabledFlag(): void
    {
        $implementation = $this->makeOpenIdImplementation([
            'openid_verid_enabled' => true,
        ]);

        $this
            ->apiUpdateImplementationOpenIdRequest($implementation, [
                'openid_verid_enabled' => 'not-a-boolean',
            ], $implementation->organization->identity)
            ->assertJsonValidationErrors(['openid_verid_enabled']);

        $this->assertTrue($implementation->refresh()->openid_verid_enabled);
    }

    /**
     * @return void
     */
    public function testUpdateImplementationOpenIdRejectsWhenOrganizationDoesNotAllowOpenId(): void
    {
        $implementation = $this->makeOpenIdImplementation([
            'openid_verid_enabled' => false,
        ], [
            'allow_openid' => false,
        ]);

        $this
            ->apiUpdateImplementationOpenIdRequest($implementation, [
                'openid_verid_enabled' => true,
            ], $implementation->organization->identity)
            ->assertForbidden();

        $this->assertFalse($implementation->refresh()->openid_verid_enabled);
    }

    /**
     * @return void
     */
    public function testUpdateImplementationOpenIdRejectsNonEmployee(): void
    {
        $implementation = $this->makeOpenIdImplementation([
            'openid_verid_enabled' => false,
        ]);

        $this
            ->apiUpdateImplementationOpenIdRequest($implementation, ['openid_verid_enabled' => true], $this->makeIdentity())
            ->assertForbidden();

        $this->assertFalse($implementation->refresh()->openid_verid_enabled);
    }

    /**
     * @return void
     */
    public function testUpdateImplementationOpenIdRejectsEmployeeWithoutManageImplementationPermission(): void
    {
        $employeeIdentity = $this->makeIdentity();

        $implementation = $this->makeOpenIdImplementation([
            'openid_verid_enabled' => false,
        ]);

        $implementation->organization->addEmployee($employeeIdentity, [
            Role::where('key', 'finance')->firstOrFail()->id,
        ]);

        $this
            ->apiUpdateImplementationOpenIdRequest($implementation, ['openid_verid_enabled' => true], $employeeIdentity)
            ->assertForbidden();

        $this->assertFalse($implementation->refresh()->openid_verid_enabled);
    }

    /**
     * @return void
     */
    public function testUpdateImplementationOpenIdRejectsImplementationFromDifferentOrganization(): void
    {
        $routeImplementation = $this->makeOpenIdImplementation();
        $implementation = $this->makeOpenIdImplementation([
            'openid_verid_enabled' => false,
        ]);

        $this
            ->apiUpdateImplementationOpenIdForOrganizationRequest(
                $routeImplementation->organization,
                $implementation,
                ['openid_verid_enabled' => true],
                $routeImplementation->organization->identity,
            )
            ->assertForbidden();

        $this->assertFalse($implementation->refresh()->openid_verid_enabled);
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
