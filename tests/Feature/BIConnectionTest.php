<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Services\BIConnectionService\BIConnectionService;
use App\Services\BIConnectionService\Models\BIConnection;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Arr;
use Tests\TestCase;
use Tests\Traits\MakesTestOrganizations;
use Throwable;

class BIConnectionTest extends TestCase
{
    use DatabaseTransactions, MakesTestOrganizations;

    /**
     * @var string
     */
    protected string $apiUrl = '/api/v1/bi/export';

    /**
     * @var string
     */
    protected string $apiOrganizationUrl = '/api/v1/platform/organizations/%s/bi-connection';

    /**
     * @return void
     * @throws Throwable
     */
    public function testValidTokenWhenEnabled(): void
    {
        $this->testValidToken();
    }

    /**
     * @return void
     * @throws Throwable
     */
    public function testAuthTypeDisabled(): void
    {
        $this->testValidToken(false);
    }

    /**
     * @return void
     */
    public function testWithoutToken(): void
    {
        $this->get($this->apiUrl)->assertForbidden();
    }

    /**
     * @param bool $enabled
     * @return void
     * @throws Throwable
     */
    protected function testValidToken(bool $enabled = true): void
    {
        $ip = '192.168.0.1';
        $this->serverVariables = ['REMOTE_ADDR' => $ip];

        /** @var Organization $organization */

        $identity = $this->makeIdentity();
        $organization = $this->makeTestOrganization($identity, [
            'allow_bi_connection' => true,
        ]);

        $apiHeaders = $this->makeApiHeaders($this->makeIdentityProxy($organization->identity), [
            'Client-Type' => 'sponsor',
        ]);

        $this->assertNotNull($organization);

        $response = $this->postJson(sprintf($this->apiOrganizationUrl, $organization->id), [
            'ips' => [$ip],
            'enabled' => $enabled,
            'data_types' => Arr::pluck(BIConnectionService::create($organization)->getDataTypes(), 'key'),
            'expiration_period' => BIConnection::EXPIRATION_PERIODS[0],
        ], $apiHeaders);

        $response->assertSuccessful();
        $response->assertJsonStructure(['data' => ['access_token']]);

        $token = $response->json('data.access_token');

        if ($enabled) {
            $this->getJson($this->apiUrl, [
                BIConnection::AUTH_TYPE_HEADER_NAME => $token,
            ])->assertSuccessful();
        } else {
            $this->getJson($this->apiUrl, [
                BIConnection::AUTH_TYPE_HEADER_NAME => $token,
            ])->assertForbidden();
        }

        $this->getJson($this->apiUrl)->assertForbidden();
    }
}
