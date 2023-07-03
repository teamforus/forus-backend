<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Services\BIConnectionService\BIConnection;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class BIConnectionTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * @var string
     */
    protected string $apiUrl = '/api/v1/bi/export';

    /**
     * @var string
     */
    protected string $apiOrganizationUrl = '/api/v1/platform/organizations/%s/update-bi-connection';

    /**
     * @var string
     */
    protected string $organizationName = 'Nijmegen';

    public function testValidTokenAuthTypeHeader(): void
    {
        $this->testValidToken(BIConnection::AUTH_TYPE_HEADER);
    }

    public function testValidTokenAuthTypeParameter(): void
    {
        $this->testValidToken(BIConnection::AUTH_TYPE_PARAMETER);
    }

    public function testAuthTypeDisabled(): void
    {
        $this->testValidToken(BIConnection::AUTH_TYPE_DISABLED);
    }

    /**
     * @return void
     */
    public function testWithoutToken(): void
    {
        $this->get($this->apiUrl)->assertForbidden();
    }

    /**
     * @param string $authType
     * @return void
     */
    protected function testValidToken(string $authType): void
    {
        /** @var Organization $organization */
        $organization = Organization::where('name', $this->organizationName)->first();
        $apiHeaders = $this->makeApiHeaders($this->makeIdentityProxy($organization->identity), [
            'Client-Type' => 'sponsor',
        ]);

        $this->assertNotNull($organization);
        $organization->update(['allow_bi_connection' => true]);

        $response = $this->patchJson(sprintf($this->apiOrganizationUrl, $organization->id), [
            'bi_connection_auth_type' => $authType
        ], $apiHeaders);

        $response->assertSuccessful();
        $response->assertJsonStructure(['data' => ['bi_connection_token']]);

        $token = $response->json('data.bi_connection_token');

        if ($authType == BIConnection::AUTH_TYPE_HEADER) {
            $this->getJson($this->apiUrl, [
                BIConnection::AUTH_TYPE_HEADER_NAME => $token,
            ])->assertSuccessful();

            $this->getJson(url_extend_get_params($this->apiUrl, [
                BIConnection::AUTH_TYPE_PARAMETER_NAME => $token,
            ]))->assertForbidden();

            $this->getJson($this->apiUrl)->assertForbidden();
        }

        if ($authType == BIConnection::AUTH_TYPE_PARAMETER) {
            $this->getJson($this->apiUrl, [
                BIConnection::AUTH_TYPE_HEADER_NAME => $token,
            ])->assertForbidden();

            $this->getJson(url_extend_get_params($this->apiUrl, [
                BIConnection::AUTH_TYPE_PARAMETER_NAME => $token,
            ]))->assertSuccessful();

            $this->getJson($this->apiUrl)->assertForbidden();
        }

        if ($authType == BIConnection::AUTH_TYPE_DISABLED) {
            $this->getJson($this->apiUrl, [
                BIConnection::AUTH_TYPE_HEADER_NAME => $token,
            ])->assertForbidden();

            $this->getJson(url_extend_get_params($this->apiUrl, [
                BIConnection::AUTH_TYPE_PARAMETER_NAME => $token,
            ]))->assertForbidden();

            $this->getJson($this->apiUrl)->assertForbidden();
        }
    }
}
