<?php

namespace Tests\Feature;

use App\Models\Identity;
use App\Models\Implementation;
use App\Models\Organization;
use App\Models\Permission;
use App\Models\PhysicalCardRequest;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;
use Tests\Traits\MakesApiRequests;
use Tests\Traits\MakesTestDCP;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestOrganizations;
use Tests\Traits\MakesTestVouchers;

class DCPConnectionTest extends TestCase
{
    use MakesTestDCP;
    use MakesTestFunds;
    use MakesApiRequests;
    use MakesTestVouchers;
    use DatabaseTransactions;
    use MakesTestOrganizations;

    /**
     * @var string[]
     */
    protected $defaultHeaders = [
        'Accept' => 'application/json',
        'Client-Type' => 'sponsor',
    ];

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->setupDCP();
    }

    /**
     * An implementation without connection returns the defaults (the model isn't stored yet).
     *
     * @return void
     */
    public function testShowDefaultsWithoutConnection(): void
    {
        [$organization, $implementation] = $this->makeOrganizationWithImplementation();

        $this->apiDCPConnectionRequest('get', $organization, $implementation)
            ->assertSuccessful()
            ->assertJsonPath('data.id', null)
            ->assertJsonPath('data.enabled', false)
            ->assertJsonPath('data.url', $this->dcpTestUrl)
            ->assertJsonPath('data.has_password', false)
            ->assertJsonPath('data.is_active', false);

        $this->assertNull($implementation->dcp_connection()->first());
    }

    /**
     * @return void
     */
    public function testUpdateConnection(): void
    {
        [$organization, $implementation] = $this->makeOrganizationWithImplementation();

        $response = $this->apiDCPConnectionRequest('patch', $organization, $implementation, [
            'enabled' => true,
            'url' => $this->dcpTestUrl,
            'username' => 'user',
            'password' => 'secret',
            'order_types' => [
                'physical_card_request' => [
                    'label' => ' Gelrepas ',
                    'item' => '',
                    'tags' => [
                        ['key' => 'printedFirstName', 'source' => 'recipient.first_name', 'required' => true],
                        ['key' => 'printedCityCode', 'value' => '1245', 'source' => 'fund.name', 'max' => '4'],
                        [
                            'key' => 'printedDateOfBirth',
                            'source' => 'recipient.birth_date',
                            'format' => 'date:d-m-Y',
                            'max' => null,
                        ],
                    ],
                ],
            ],
        ]);

        $response
            ->assertSuccessful()
            ->assertJsonPath('data.enabled', true)
            ->assertJsonPath('data.url', $this->dcpTestUrl)
            ->assertJsonPath('data.username', 'user')
            ->assertJsonPath('data.has_password', true)
            ->assertJsonPath('data.is_active', true)
            ->assertJsonMissingPath('data.password');

        $connection = $implementation->dcp_connection()->first();

        // the fixed value wins over the source, the empty values are dropped (json: the key order isn't kept)
        $this->assertEquals([
            'physical_card_request' => [
                'label' => 'Gelrepas',
                'tags' => [
                    ['key' => 'printedFirstName', 'source' => 'recipient.first_name', 'required' => true],
                    ['key' => 'printedCityCode', 'value' => '1245', 'required' => false, 'max' => 4],
                    [
                        'key' => 'printedDateOfBirth',
                        'source' => 'recipient.birth_date',
                        'required' => false,
                        'format' => 'date:d-m-Y',
                    ],
                ],
            ],
        ], $connection->order_types);

        // the password is encrypted and kept when the field is left empty
        $this->assertNotSame('secret', DB::table('dcp_connections')->where('id', $connection->id)->value('password'));
        $this->assertSame('secret', $connection->password);

        $this->apiDCPConnectionRequest('patch', $organization, $implementation, [
            'enabled' => true,
            'url' => $this->dcpTestUrl,
            'username' => 'other-user',
            'password' => '',
            'order_types' => $this->makeDCPGelrepasOrderTypes(),
        ])->assertSuccessful()->assertJsonPath('data.has_password', true);

        $this->assertSame('secret', $connection->refresh()->password);
        $this->assertSame('other-user', $connection->username);

        // disabled without credentials is allowed
        $this->apiDCPConnectionRequest('patch', $organization, $implementation, [
            'enabled' => false,
            'url' => $this->dcpTestUrl,
            'username' => '',
        ])->assertSuccessful()->assertJsonPath('data.is_active', false);
    }

    /**
     * @return void
     */
    public function testUpdateConnectionValidation(): void
    {
        [$organization, $implementation] = $this->makeOrganizationWithImplementation();

        $valid = [
            'enabled' => true,
            'url' => $this->dcpTestUrl,
            'username' => 'user',
            'password' => 'secret',
            'order_types' => $this->makeDCPGelrepasOrderTypes(),
        ];

        $tag = fn (array $data) => [...$valid, 'order_types' => ['physical_card_request' => [
            'label' => 'Gelrepas',
            'tags' => [$data],
        ]]];

        $cases = [
            'url' => [...$valid, 'url' => 'https://other-dcp.test'],
            'username' => [...$valid, 'username' => ''],
            'password' => [...$valid, 'password' => ''],
            'order_types' => [...$valid, 'order_types' => ['unknown_type' => []]],
            'order_types.physical_card_request.label' => [...$valid, 'order_types' => [
                'physical_card_request' => ['tags' => [['key' => 'printedFirstName', 'value' => 'x']]],
            ]],
            'order_types.physical_card_request.tags.0.source' => $tag(['key' => 'printedFirstName', 'source' => 'secret.field']),
            'order_types.physical_card_request.tags.0.key' => $tag(['key' => 'key', 'value' => 'x']),
            'order_types.physical_card_request.tags.0.format' => $tag(['key' => 'printedFirstName', 'value' => 'x', 'format' => 'reverse']),
            'order_types.physical_card_request.tags.0.max' => $tag(['key' => 'printedFirstName', 'value' => 'x', 'max' => 0]),
        ];

        foreach ($cases as $field => $data) {
            $this->apiDCPConnectionRequest('patch', $organization, $implementation, $data)
                ->assertJsonValidationErrorFor($field);
        }

        // a tag needs a source or a fixed value, the keys must be unique and without special characters
        $this->apiDCPConnectionRequest('patch', $organization, $implementation, $tag(['key' => 'printedFirstName']))
            ->assertJsonValidationErrorFor('order_types.physical_card_request.tags.0.source');

        $this->apiDCPConnectionRequest('patch', $organization, $implementation, $tag(['key' => 'printed name', 'value' => 'x']))
            ->assertJsonValidationErrorFor('order_types.physical_card_request.tags.0.key');

        $this->apiDCPConnectionRequest('patch', $organization, $implementation, [...$valid, 'order_types' => [
            'physical_card_request' => ['label' => 'Gelrepas', 'tags' => [
                ['key' => 'printedFirstName', 'value' => 'x'],
                ['key' => 'printedFirstName', 'value' => 'y'],
            ]],
        ]])->assertJsonValidationErrorFor('order_types.physical_card_request.tags.1.key');

        $this->assertNull($implementation->dcp_connection()->first());
    }

    /**
     * @return void
     */
    public function testOptions(): void
    {
        [$organization, $implementation] = $this->makeOrganizationWithImplementation();

        $response = $this->apiDCPConnectionRequest('get', $organization, $implementation, path: '/options')
            ->assertSuccessful()
            ->assertJsonPath('data.urls', [$this->dcpTestUrl])
            ->assertJsonPath('data.orderable_types.0.key', 'physical_card_request');

        $this->assertSame(
            PhysicalCardRequest::DCP_SOURCE_FIELDS,
            array_column($response->json('data.orderable_types.0.source_fields'), 'key'),
        );

        $this->assertSame(Config::get('dcp.formats'), array_column($response->json('data.formats'), 'key'));
    }

    /**
     * @return void
     */
    public function testConnectionTest(): void
    {
        [$organization, $implementation] = $this->makeOrganizationWithImplementation();
        $credentials = ['url' => $this->dcpTestUrl, 'username' => 'user', 'password' => 'secret'];

        // the credentials are checked by requesting an order which doesn't exist
        $this->fakeDCPApi(get: Http::response(['statusId' => -1], 404));

        $this->apiDCPConnectionRequest('post', $organization, $implementation, $credentials, '/test')
            ->assertSuccessful()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', trans('dcp.connection_test.success'));

        $this->fakeDCPApi(get: Http::response([], 401));

        $this->apiDCPConnectionRequest('post', $organization, $implementation, $credentials, '/test')
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', trans('dcp.connection_test.unauthorized'));

        $this->fakeDCPApi(get: Http::failedConnection());

        $this->apiDCPConnectionRequest('post', $organization, $implementation, $credentials, '/test')
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', trans('dcp.connection_test.unreachable'));

        // missing credentials
        $this->apiDCPConnectionRequest('post', $organization, $implementation, ['username' => 'user'], '/test')
            ->assertStatus(422)
            ->assertJsonPath('message', trans('dcp.connection_test.missing'));

        $this->apiDCPConnectionRequest('post', $organization, $implementation, [
            ...$credentials,
            'url' => 'https://other-dcp.test',
        ], '/test')->assertJsonValidationErrorFor('url');

        // the saved password is used when the field is empty
        $this->makeTestDCPConnection($implementation, ['password' => 'saved-secret']);
        $this->fakeDCPApi(get: Http::response(['statusId' => -1], 404));

        $this->apiDCPConnectionRequest('post', $organization, $implementation, ['username' => 'form-user'], '/test')
            ->assertJsonPath('success', true);

        $request = collect(Http::recorded(fn (Request $request) => $request->method() === 'GET'))->last()[0];
        $this->assertTrue($request->hasHeader('Authorization', 'Basic ' . base64_encode('form-user:saved-secret')));
    }

    /**
     * @return void
     */
    public function testPermissions(): void
    {
        [$organization, $implementation] = $this->makeOrganizationWithImplementation();
        [$otherOrganization] = $this->makeOrganizationWithImplementation();

        $manager = $this->makeTestEmployeeWithPermissions($organization, [Permission::MANAGE_IMPLEMENTATION]);
        $viewer = $this->makeTestEmployeeWithPermissions($organization, [Permission::MANAGE_VOUCHERS]);

        $this->apiDCPConnectionRequest('get', $organization, $implementation, identity: $manager->identity)
            ->assertSuccessful();

        $this->apiDCPConnectionRequest('get', $organization, $implementation, identity: $viewer->identity)
            ->assertForbidden();

        // the implementation of another organization
        $this->apiDCPConnectionRequest('get', $otherOrganization, $implementation, identity: $otherOrganization->identity)
            ->assertForbidden();

        // physical cards are not allowed for the organization (the body is validated before the permissions)
        $organization->forceFill(['allow_physical_cards' => false])->save();
        $data = ['enabled' => false, 'url' => $this->dcpTestUrl];

        foreach (['get' => '', 'patch' => '', 'post' => '/test'] as $method => $path) {
            $this->apiDCPConnectionRequest($method, $organization, $implementation, $data, $path)->assertForbidden();
        }

        $this->apiDCPConnectionRequest('get', $organization, $implementation, path: '/options')->assertForbidden();
    }

    /**
     * The menu and the DCP columns on the dashboard are shown when the organization uses DCP.
     *
     * @return void
     */
    public function testOrganizationHasDCP(): void
    {
        [$organization, $implementation] = $this->makeOrganizationWithImplementation();

        $this->assertHasDCP($organization, false);

        $connection = $this->makeTestDCPConnection($implementation);
        $this->assertHasDCP($organization, true);

        // DCP is disabled globally
        Config::set('dcp.enabled', false);
        $this->assertHasDCP($organization, false);
        Config::set('dcp.enabled', true);

        // the connection is disabled, but there are orders to show
        $setup = $this->makeTestDCPSetup();
        $this->makeTestDCPOrder($setup);
        $setup['connection']->update(['enabled' => false]);

        $this->assertHasDCP($setup['organization'], true);

        $connection->update(['enabled' => false]);
        $this->assertHasDCP($organization, false);

        // physical cards are not allowed
        $connection->update(['enabled' => true]);
        $organization->forceFill(['allow_physical_cards' => false])->save();
        $this->assertHasDCP($organization, false);

        // not shown on the provider dashboard
        $this->getJson("/api/v1/platform/organizations/$organization->id", [
            ...$this->makeApiHeaders($organization->identity),
            'Client-Type' => 'provider',
        ])->assertSuccessful()->assertJsonMissingPath('data.has_dcp');
    }

    /**
     * @return array{0: Organization, 1: Implementation}
     */
    protected function makeOrganizationWithImplementation(): array
    {
        $organization = $this->makeTestOrganization($this->makeIdentity($this->makeUniqueEmail()), [
            'allow_physical_cards' => true,
        ]);

        return [$organization, $this->makeTestImplementation($organization)];
    }

    /**
     * @param string $method
     * @param Organization $organization
     * @param Implementation $implementation
     * @param array $data
     * @param string $path
     * @param Identity|null $identity
     * @return TestResponse
     */
    protected function apiDCPConnectionRequest(
        string $method,
        Organization $organization,
        Implementation $implementation,
        array $data = [],
        string $path = '',
        ?Identity $identity = null,
    ): TestResponse {
        return $this->json(
            $method,
            "/api/v1/platform/organizations/$organization->id/implementations/$implementation->id/dcp-connection$path",
            $data,
            $this->makeApiHeaders($identity ?: $organization->identity),
        );
    }

    /**
     * @param Organization $organization
     * @param bool $hasDCP
     * @return void
     */
    protected function assertHasDCP(Organization $organization, bool $hasDCP): void
    {
        $this->getJson("/api/v1/platform/organizations/$organization->id", $this->makeApiHeaders($organization->identity))
            ->assertSuccessful()
            ->assertJsonPath('data.has_dcp', $hasDCP);
    }
}
