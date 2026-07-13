<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestOrganizations;
use Throwable;

class ImplementationAuthPageTest extends TestCase
{
    use WithFaker;
    use MakesTestFunds;
    use MakesTestOrganizations;
    use DatabaseTransactions;

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
            'auth_page_info_enabled' => false,
            'auth_page_info_title' => $this->faker->text(50),
            'auth_page_info_description' => null,
        ], $replace);
    }
}
