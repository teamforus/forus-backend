<?php

namespace Tests\Feature\IdentityProviders;

use App\Models\Implementation;
use App\Models\ProfileRecord;
use App\Services\IdentityProviderService\Models\IdentityProviderConnection;
use App\Services\IdentityProviderService\Support\IdentityProviderScimDiscovery;
use App\Services\IdentityProviderService\Support\IdentityProviderScimUserPayload;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestIdentityProviders;
use Tests\Traits\MakesTestOrganizations;

class IdentityProviderScimProfileTest extends TestCase
{
    use DatabaseTransactions;
    use MakesTestFunds;
    use MakesTestIdentityProviders;
    use MakesTestOrganizations;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        Config::set('identity_providers.enabled', true);
    }

    /**
     * @return void
     */
    public function testDiscoveryAdvertisesOptionalProfileExtension(): void
    {
        [$connection, $token] = $this->makeIdentityProviderScimContext();
        $headers = ['Authorization' => "Bearer $token", 'Accept' => 'application/scim+json'];
        $url = "/api/v1/scim/$connection->uid/v2";

        $this->getJson($url . '/ResourceTypes/User', $headers)->assertOk()->assertJsonPath('schemaExtensions', [[
            'schema' => IdentityProviderScimDiscovery::SCHEMA_FORUS_USER, 'required' => false,
        ]]);

        $this->getJson($url . '/Schemas', $headers)->assertOk()->assertJsonPath('Resources.*.id', [
            IdentityProviderScimDiscovery::SCHEMA_USER, IdentityProviderScimDiscovery::SCHEMA_FORUS_USER,
        ]);

        $this->getJson($url . '/Schemas/' . IdentityProviderScimDiscovery::SCHEMA_FORUS_USER, $headers)
            ->assertOk()
            ->assertJsonPath('attributes.*.name', ['birthDate', 'gender'])
            ->assertJsonPath('attributes.*.required', [false, false])
            ->assertJsonPath('attributes.1.canonicalValues', ['male', 'female', 'unknown', 'unspecified']);
    }

    /**
     * @return void
     */
    public function testCreateExposesProfileValuesInScimAndExistingProfileViews(): void
    {
        [$connection, $token] = $this->makeIdentityProviderScimContext();
        $connection->organization->forceFill(['allow_profiles' => true])->save();
        $payload = $this->makeIdentityProviderScimUserPayload([
            'schemas' => [
                IdentityProviderScimDiscovery::SCHEMA_FORUS_USER, IdentityProviderScimDiscovery::SCHEMA_USER,
                IdentityProviderScimDiscovery::SCHEMA_ENTERPRISE_USER,
            ],
            IdentityProviderScimDiscovery::SCHEMA_FORUS_USER => ['birthDate' => '1992-02-29', 'gender' => 'female'],
        ]);
        $membership = $this->provisionIdentityProviderRequester($connection, $token, $payload);

        $response = $this->apiGetIdentityProviderScimUsersRequest($connection, $token, $membership->uid)->assertOk();
        $this->assertSame(
            ['birthDate' => '1992-02-29', 'gender' => 'female'],
            $response->json()[IdentityProviderScimDiscovery::SCHEMA_FORUS_USER],
        );
        $this->assertContains(IdentityProviderScimDiscovery::SCHEMA_FORUS_USER, $response->json('schemas'));

        $this->apiViewIdentityRequest(
            $connection->organization_id,
            $membership->identity_id,
            $connection->organization->identity,
        )->assertOk()
            ->assertJsonPath('data.records.birth_date.0.value', '1992-02-29')
            ->assertJsonPath('data.records.gender.0.value', 'vrouwelijk')
            ->assertJsonPath('data.records.birth_date.0.source', ProfileRecord::SOURCE_ENTRA)
            ->assertJsonPath('data.records.gender.0.source', ProfileRecord::SOURCE_ENTRA);

        $implementation = $this->makeTestImplementation($connection->organization);
        $proxy = $this->makeIdentityProviderProxy($membership);

        $this->getJson('/api/v1/platform/profile', $this->makeApiHeaders($proxy, [
            'Client-Type' => Implementation::FRONTEND_WEBSHOP, 'Client-Key' => $implementation->key,
        ]))->assertOk()
            ->assertJsonPath('records.birth_date.0.value', '1992-02-29')
            ->assertJsonPath('records.gender.0.value', 'vrouwelijk');

        $event = $connection->logs()
            ->where('event', IdentityProviderConnection::EVENT_SCIM_USER_CREATED)->firstOrFail();
        $this->assertContains('birthDate', $event->data['context']['requested_fields']);
        $this->assertContains('gender', $event->data['context']['requested_fields']);
        $this->assertStringNotContainsString('1992-02-29', json_encode($event->data));
        $this->assertStringNotContainsString('female', json_encode($event->data));
    }

    /**
     * @return void
     */
    public function testPatchUpdatesPreservesAndClearsProfileValuesWithSponsorScopedHistory(): void
    {
        [$connection, $token] = $this->makeIdentityProviderScimContext();
        $membership = $this->provisionIdentityProviderRequester(
            $connection,
            $token,
            $this->makeIdentityProviderScimUserPayload(),
        );
        $profile = $membership->identity->profiles()
            ->where('organization_id', $connection->organization_id)->firstOrFail();
        $otherProfile = $membership->identity->profiles()->create([
            'organization_id' => $this->makeTestOrganization($this->makeIdentity())->id,
        ]);
        $otherProfile->updateRecords(['birth_date' => '1980-01-01', 'gender' => 'onbekend']);
        $otherRecords = $otherProfile->profile_records()->get()->toArray();

        $patch = [
            'schemas' => [IdentityProviderScimUserPayload::SCHEMA_PATCH],
            'Operations' => [['op' => 'add', 'value' => [
                IdentityProviderScimDiscovery::SCHEMA_FORUS_USER => ['birthDate' => '1990-05-10', 'gender' => 'male'],
            ]]],
        ];
        $this->apiIdentityProviderScimUsersRequest('PATCH', $connection, $token, $patch, $membership->uid)->assertOk();

        $records = $profile->profile_records()->count();
        $events = $connection->logs()->count();
        $this->apiIdentityProviderScimUsersRequest('PATCH', $connection, $token, $patch, $membership->uid)->assertOk();
        $this->assertSame($records, $profile->profile_records()->count());
        $this->assertSame($events, $connection->logs()->count());

        $patch['Operations'] = [['op' => 'replace', 'path' => 'name.givenName', 'value' => 'Janet']];
        $response = $this->apiIdentityProviderScimUsersRequest('PATCH', $connection, $token, $patch, $membership->uid)
            ->assertOk();
        $this->assertSame(
            ['birthDate' => '1990-05-10', 'gender' => 'male'],
            $response->json()[IdentityProviderScimDiscovery::SCHEMA_FORUS_USER],
        );

        $patch['Operations'] = [
            ['op' => 'replace', 'value' => [
                IdentityProviderScimDiscovery::SCHEMA_FORUS_USER . ':birthDate' => '1991-06-11',
            ]],
            ['op' => 'replace', 'path' => IdentityProviderScimDiscovery::SCHEMA_FORUS_USER . ':gender',
                'value' => 'unspecified'],
        ];
        $response = $this->apiIdentityProviderScimUsersRequest('PATCH', $connection, $token, $patch, $membership->uid)
            ->assertOk();
        $this->assertSame(
            ['birthDate' => '1991-06-11', 'gender' => 'unspecified'],
            $response->json()[IdentityProviderScimDiscovery::SCHEMA_FORUS_USER],
        );

        $patch['Operations'] = [[
            'op' => 'remove', 'path' => IdentityProviderScimDiscovery::SCHEMA_FORUS_USER . ':birthDate',
        ]];
        $response = $this->apiIdentityProviderScimUsersRequest('PATCH', $connection, $token, $patch, $membership->uid)
            ->assertOk();
        $this->assertSame(
            ['gender' => 'unspecified'],
            $response->json()[IdentityProviderScimDiscovery::SCHEMA_FORUS_USER],
        );

        $patch['Operations'] = [['op' => 'remove', 'path' => IdentityProviderScimDiscovery::SCHEMA_FORUS_USER]];
        $this->apiIdentityProviderScimUsersRequest('PATCH', $connection, $token, $patch, $membership->uid)->assertOk();

        $this->assertSame(['', '1991-06-11', '1990-05-10'], $profile->profile_records()
            ->whereRelation('record_type', 'key', 'birth_date')->pluck('value')->all());
        $this->assertSame(['', 'niet gespecificeerd', 'mannelijk'], $profile->profile_records()
            ->whereRelation('record_type', 'key', 'gender')->pluck('value')->all());
        $this->assertSame($otherRecords, $otherProfile->profile_records()->get()->toArray());
    }

    /**
     * @return void
     */
    public function testPutReplacesAndClearsOptionalProfileFields(): void
    {
        [$connection, $token] = $this->makeIdentityProviderScimContext();
        $payload = $this->makeIdentityProviderScimUserPayload([
            IdentityProviderScimDiscovery::SCHEMA_FORUS_USER => ['birthDate' => '1990-05-10', 'gender' => 'male'],
        ]);
        $membership = $this->provisionIdentityProviderRequester($connection, $token, $payload);
        $payload[IdentityProviderScimDiscovery::SCHEMA_FORUS_USER] = ['gender' => 'unknown'];

        $response = $this->apiIdentityProviderScimUsersRequest('PUT', $connection, $token, $payload, $membership->uid)
            ->assertOk();
        $this->assertSame(['gender' => 'unknown'], $response->json()[IdentityProviderScimDiscovery::SCHEMA_FORUS_USER]);

        unset($payload[IdentityProviderScimDiscovery::SCHEMA_FORUS_USER]);

        $this->apiIdentityProviderScimUsersRequest('PUT', $connection, $token, $payload, $membership->uid)->assertOk();
        $this->apiViewIdentityRequest(
            $connection->organization_id,
            $membership->identity_id,
            $connection->organization->identity,
        )->assertOk()
            ->assertJsonPath('data.records.birth_date.*.value', ['', '1990-05-10'])
            ->assertJsonPath('data.records.gender.*.value', ['', 'onbekend', 'mannelijk']);
    }

    /**
     * @return void
     */
    public function testInvalidProfileUpdateRejectsTheWholePatchAndAuditsFieldNamesOnly(): void
    {
        [$connection, $token] = $this->makeIdentityProviderScimContext();
        $membership = $this->provisionIdentityProviderRequester(
            $connection,
            $token,
            $this->makeIdentityProviderScimUserPayload(),
        );
        $proxy = $this->makeIdentityProviderProxy($membership);
        $profile = $membership->identity->profiles()
            ->where('organization_id', $connection->organization_id)->firstOrFail();
        $records = $profile->profile_records()->get()->toArray();

        $this->apiIdentityProviderScimUsersRequest('PATCH', $connection, $token, [
            'schemas' => [IdentityProviderScimUserPayload::SCHEMA_PATCH],
            'Operations' => [['op' => 'replace', 'value' => [
                'active' => false,
                'name.givenName' => 'Rejected name',
                IdentityProviderScimDiscovery::SCHEMA_FORUS_USER . ':birthDate' => '1991-02-29',
                IdentityProviderScimDiscovery::SCHEMA_FORUS_USER . ':gender' => 'female',
            ]]],
        ], $membership->uid)->assertStatus(400)->assertJsonPath('scimType', 'invalidValue');

        $this->assertSame($records, $profile->profile_records()->get()->toArray());
        $this->assertTrue($membership->refresh()->isProvisioningActive());
        $this->assertIdentityProviderProxy($proxy->refresh(), $membership);

        $event = $connection->logs()
            ->where('event', IdentityProviderConnection::EVENT_SCIM_USER_UPDATE_FAILED)->firstOrFail();
        $this->assertSame(
            ['active', 'name.givenName', 'birthDate', 'gender'],
            $event->data['context']['requested_fields'],
        );
        $this->assertStringNotContainsString('1991-02-29', json_encode($event->data));
        $this->assertStringNotContainsString('female', json_encode($event->data));

        $response = $this->apiGetIdentityProviderConnectionEventsRequest(
            $connection->organization,
            $connection,
            ['category' => 'requester_provisioning', 'outcome' => 'failure'],
            $connection->organization->identity,
        )->assertOk();
        $this->assertContains(
            ['key' => 'birthDate', 'name' => 'Geboortedatum'],
            $response->json('data.0.requested_fields'),
        );
        $this->assertContains(['key' => 'gender', 'name' => 'Geslacht'], $response->json('data.0.requested_fields'));
    }
}
