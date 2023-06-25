<?php

namespace Tests\Feature;

use App\Models\BIConnection;
use App\Models\Organization;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class BIConnectionTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * @var string
     */
    protected string $apiUrl = '/api/v1/exporteren';

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
    public function testValidToken(): void
    {
        /** @var Organization $organization */
        $organization = Organization::where('name', $this->organizationName)->first();
        $this->assertNotNull($organization);

        $organization->update(['allow_bi_connection' => true]);

        $identity = $organization->identity;

        $proxy = $this->makeIdentityProxy($identity);
        $headers = $this->makeApiHeaders($proxy);

        $response = $this->post(sprintf($this->apiOrganizationUrl, $organization->id), [
            'auth_type' => BIConnection::AUTH_TYPE_HEADER
        ], $headers);

        $response->assertSuccessful();
        $response->assertJsonStructure(['data' => ['token']]);

        $token = $response->json('data.token');

        $response = $this->get($this->apiUrl, [
            'X-API-KEY' => $token
        ]);

        $response->assertSuccessful();
    }

    /**
     * @return void
     */
    public function testWithoutToken(): void
    {
        $this->get($this->apiUrl)->assertForbidden();
    }
}
