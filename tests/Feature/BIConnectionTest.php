<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Services\BIConnectionService\Models\BIConnection;
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
    protected string $apiOrganizationUrl = '/api/v1/platform/organizations/%s/bi-connections';

    /**
     * @var string
     */
    protected string $organizationName = 'Nijmegen';

    /**
     * @return void
     */
    public function testValidTokenAuthTypeHeader(): void
    {
        $this->testValidToken(BIConnection::AUTH_TYPE_HEADER);
    }

    /**
     * @return void
     */
    public function testValidTokenAuthTypeParameter(): void
    {
        $this->testValidToken(BIConnection::AUTH_TYPE_PARAMETER);
    }

    /**
     * @return void
     */
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
        $ip = '192.168.0.1';
        $this->serverVariables = ['REMOTE_ADDR' => $ip];

        /** @var Organization $organization */
        $organization = Organization::where('name', $this->organizationName)->first();
        $apiHeaders = $this->makeApiHeaders($this->makeIdentityProxy($organization->identity), [
            'Client-Type' => 'sponsor',
        ]);

        $this->assertNotNull($organization);
        $organization->update(['allow_bi_connection' => true]);
        $organization->bi_connection()->delete();

        $response = $this->postJson(sprintf($this->apiOrganizationUrl, $organization->id), [
            'ips' => [$ip],
            'auth_type' => $authType,
            'data_types' => array_keys(BIConnection::DATA_TYPES),
            'expiration_period' => BIConnection::EXPIRATION_PERIODS[0],
        ], $apiHeaders);

        $response->assertSuccessful();
        $response->assertJsonStructure(['data' => ['access_token']]);

        $token = $response->json('data.access_token');

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
