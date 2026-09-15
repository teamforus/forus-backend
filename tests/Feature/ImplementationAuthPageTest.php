<?php

namespace Tests\Feature;

use App\Models\Implementation;
use App\Models\Organization;
use App\Models\Role;
use App\Services\DigIdService\Models\DigIdSession;
use App\Services\IdentityProviderService\Models\IdentityProviderConnection;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestIdentityProviders;
use Tests\Traits\MakesTestOrganizations;
use Throwable;

class ImplementationAuthPageTest extends TestCase
{
    use WithFaker;
    use MakesTestFunds;
    use MakesTestIdentityProviders;
    use MakesTestOrganizations;
    use DatabaseTransactions;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        Config::set('identity_providers.enabled', true);
        $this->withHeaders(['Client-Type' => Implementation::FRONTEND_SPONSOR_DASHBOARD]);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testCmsManagerCanToggleEntraWithoutChangingExistingLoginOptions(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity(), [
            'allow_identity_providers' => Organization::ALLOW_IDENTITY_PROVIDERS_SSO,
        ]);

        $implementation = $this->makeTestImplementation($organization, [
            'digid_connection_type' => DigIdSession::CONNECTION_TYPE_CGI,
            'digid_enabled' => true,
            'digid_app_id' => 'test-app',
            'digid_shared_secret' => 'test-secret',
            'digid_a_select_server' => 'https://digid.example.test',
        ]);

        $this->makeEntraConnection($organization);

        $manager = $this->makeIdentity();

        $organization->addEmployee($manager, [
            Role::where('key', 'implementation_communication_manager')->firstOrFail()->id,
        ]);

        foreach ([true, false] as $enabled) {
            $this->apiUpdateImplementationAuthPageRequest($implementation, $this->makeAuthPageData([
                'auth_page_login_digid' => true,
                'entra_login_enabled' => $enabled,
            ]), $manager)
                ->assertOk()
                ->assertJsonPath('data.entra_login_enabled', $enabled)
                ->assertJsonPath('data.entra_login_available', true);

            $implementation->refresh();

            $this->assertSame($enabled, $implementation->entra_login_enabled);
            $this->assertTrue($implementation->auth_page_login_email);
            $this->assertTrue($implementation->auth_page_login_digid);
            $this->assertTrue($implementation->auth_page_login_qr);

            $this->assertWebshopLoginOptions($implementation, $enabled
                ? ['email', 'digid', 'qr', 'entra']
                : ['email', 'digid', 'qr']);
        }
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testEntraUpdateRequiresCmsPermission(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity(), [
            'allow_identity_providers' => Organization::ALLOW_IDENTITY_PROVIDERS_SSO,
        ]);

        $implementation = $this->makeTestImplementation($organization, ['entra_login_enabled' => false]);
        $this->makeEntraConnection($organization);

        $employee = $this->makeIdentity();
        $organization->addEmployee($employee, [Role::where('key', 'operation_officer')->firstOrFail()->id]);

        $this->apiUpdateImplementationAuthPageRequest($implementation, $this->makeAuthPageData([
            'entra_login_enabled' => true,
        ]), $employee)->assertForbidden();

        $this->assertFalse($implementation->refresh()->entra_login_enabled);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testAvailableEntraCanBeTheOnlySelectedLoginOption(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity(), [
            'allow_identity_providers' => Organization::ALLOW_IDENTITY_PROVIDERS_SSO,
        ]);

        $implementation = $this->makeTestImplementation($organization);
        $this->makeEntraConnection($organization);

        $this->apiUpdateImplementationAuthPageRequest($implementation, $this->makeAuthPageData([
            'auth_page_login_email' => false,
            'auth_page_login_qr' => false,
            'entra_login_enabled' => true,
        ]), $organization->identity)
            ->assertOk()
            ->assertJsonPath('data.entra_login_enabled', true);

        $this->assertTrue($implementation->refresh()->entra_login_enabled);
        $this->assertWebshopLoginOptions($implementation, ['entra']);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testPausedConnectionKeepsStoredEntraSelectionButFiltersPublicOptions(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity(), [
            'allow_identity_providers' => Organization::ALLOW_IDENTITY_PROVIDERS_SSO,
        ]);

        $implementation = $this->makeTestImplementation($organization, [
            'auth_page_login_email' => true,
            'auth_page_login_digid' => false,
            'auth_page_login_qr' => true,
            'entra_login_enabled' => true,
        ]);

        $connection = $this->makeEntraConnection($organization);

        $this->assertWebshopLoginOptions($implementation, ['email', 'qr', 'entra']);

        $connection->update(['status' => IdentityProviderConnection::STATUS_PAUSED]);

        $this->assertWebshopLoginOptions($implementation, ['email', 'qr']);

        $this->apiUpdateImplementationAuthPageRequest($implementation, $this->makeAuthPageData([
            'entra_login_enabled' => true,
        ]), $organization->identity)
            ->assertOk()
            ->assertJsonPath('data.entra_login_enabled', true)
            ->assertJsonPath('data.entra_login_configured', true)
            ->assertJsonPath('data.entra_login_available', false);

        $this->assertTrue($implementation->refresh()->entra_login_enabled);

        $connection->update(['status' => IdentityProviderConnection::STATUS_ENABLED]);

        $this->assertWebshopLoginOptions($implementation, ['email', 'qr', 'entra']);
    }

    /**
     * @return void
     */
    public function testPublicEntraOptionRequiresModuleAndOrganizationCapability(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity(), [
            'allow_identity_providers' => Organization::ALLOW_IDENTITY_PROVIDERS_SSO,
        ]);

        $implementation = $this->makeTestImplementation($organization, [
            'auth_page_login_email' => true,
            'auth_page_login_digid' => false,
            'auth_page_login_qr' => true,
            'entra_login_enabled' => true,
        ]);

        $this->makeEntraConnection($organization);

        $this->assertWebshopLoginOptions($implementation, ['email', 'qr', 'entra']);

        Config::set('identity_providers.enabled', false);

        $this->assertWebshopLoginOptions($implementation, ['email', 'qr']);

        Config::set('identity_providers.enabled', true);

        $this->assertWebshopLoginOptions($implementation, ['email', 'qr', 'entra']);

        $organization->forceFill(['allow_identity_providers' => Organization::ALLOW_IDENTITY_PROVIDERS_NO])->save();

        $this->assertWebshopLoginOptions($implementation, ['email', 'qr']);
        $this->assertTrue($implementation->refresh()->entra_login_enabled);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testUpdateImplementationAuthPageRejectsUnavailableSelectedOptions(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $implementation = $this->makeTestImplementation($organization);

        $implementation->update([
            'digid_enabled' => false,
        ]);

        $request = $this->apiUpdateImplementationAuthPageRequest($implementation, $this->makeAuthPageData([
            'auth_page_login_email' => false,
            'auth_page_login_digid' => true,
            'auth_page_login_qr' => false,
        ]), $implementation->organization->identity);

        $request->assertJsonValidationErrors(['auth_page_login_options']);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testUpdateImplementationAuthPageRejectsDisabledLoginOptions(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $implementation = $this->makeTestImplementation($organization);

        $request = $this->apiUpdateImplementationAuthPageRequest($implementation, $this->makeAuthPageData([
            'auth_page_login_email' => false,
            'auth_page_login_digid' => false,
            'auth_page_login_qr' => false,
        ]), $implementation->organization->identity);

        $request->assertJsonValidationErrors(['auth_page_login_options']);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testUpdateImplementationAuthPageKeepsUnavailableDesiredOptionsButFiltersEffectiveOptions(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $implementation = $this->makeTestImplementation($organization);

        $implementation->update([
            'digid_enabled' => false,
        ]);

        $request = $this->apiUpdateImplementationAuthPageRequest($implementation, $this->makeAuthPageData([
            'auth_page_login_email' => false,
            'auth_page_login_digid' => true,
            'auth_page_login_qr' => true,
        ]), $implementation->organization->identity);

        $request->assertSuccessful();
        $implementation->refresh();

        $this->assertFalse($implementation->auth_page_login_email);
        $this->assertTrue($implementation->auth_page_login_digid);
        $this->assertTrue($implementation->auth_page_login_qr);
        $this->assertEquals(['qr'], $implementation->authPageConfig()['login_options']);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testAuthPageConfigFiltersUnavailableDigiDForWebshopLoginOptions(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $implementation = $this->makeTestImplementation($organization);

        $implementation->update([
            'digid_enabled' => false,
            'auth_page_login_email' => true,
            'auth_page_login_digid' => true,
            'auth_page_login_qr' => true,
        ]);

        $this->assertEquals(['email', 'qr'], $implementation->refresh()->authPageConfig()['login_options']);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testUpdateImplementationAuthPageAllowsEmptyInfoTitleWhenInfoSectionIsDisabled(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $implementation = $this->makeTestImplementation($organization);

        $implementation->update([
            'auth_page_info_title' => 'Bestaande titel',
        ]);

        $request = $this->apiUpdateImplementationAuthPageRequest($implementation, $this->makeAuthPageData([
            'auth_page_info_enabled' => false,
            'auth_page_info_title' => '',
        ]), $implementation->organization->identity);

        $request->assertSuccessful();
        $implementation->refresh();

        $this->assertFalse($implementation->auth_page_info_enabled);
        $this->assertEmpty($implementation->auth_page_info_title);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testUpdateImplementationAuthPageAllowsEmptyInfoTitleWhenInfoSectionIsEnabled(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $implementation = $this->makeTestImplementation($organization);

        $request = $this->apiUpdateImplementationAuthPageRequest($implementation, $this->makeAuthPageData([
            'auth_page_info_enabled' => true,
            'auth_page_info_title' => '',
            'auth_page_info_description' => '',
        ]), $implementation->organization->identity);

        $request->assertSuccessful();
        $implementation->refresh();

        $this->assertTrue($implementation->auth_page_info_enabled);
        $this->assertEmpty($implementation->auth_page_info_title);
    }

    /**
     * @param array $replace
     * @return array
     */
    protected function makeAuthPageData(array $replace = []): array
    {
        return array_merge([
            'auth_page_title' => $this->faker->text(50),
            'auth_page_login_title' => $this->faker->text(50),
            'auth_page_login_email' => true,
            'auth_page_login_digid' => false,
            'auth_page_login_qr' => true,
            'entra_login_enabled' => false,
            'auth_page_info_enabled' => false,
            'auth_page_info_title' => $this->faker->text(50),
            'auth_page_info_description' => null,
        ], $replace);
    }

    /**
     * @param Implementation $implementation
     * @param array $options
     * @return void
     */
    protected function assertWebshopLoginOptions(Implementation $implementation, array $options): void
    {
        Implementation::clearMemo();

        $this->getJson('/api/v1/platform/config/webshop', [
            'Client-Type' => Implementation::FRONTEND_WEBSHOP,
            'Client-Key' => $implementation->key,
        ])
            ->assertOk()
            ->assertJsonPath('auth_page.login_options', $options);
    }
}
