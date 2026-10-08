<?php

namespace Tests\Feature\IdentityProviders;

use App\Models\Identity;
use App\Models\Profile;
use App\Models\ProfileRecord;
use App\Models\Voucher;
use App\Services\EventLogService\Models\EventLog;
use App\Services\IdentityProviderService\Models\IdentityProviderConnection;
use App\Services\IdentityProviderService\Models\IdentityProviderMembership;
use App\Services\IdentityProviderService\Support\IdentityProviderScimDiscovery;
use App\Services\IdentityProviderService\Support\IdentityProviderScimUserPayload;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Tests\TestCase;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestIdentityProviders;
use Tests\Traits\MakesTestOrganizations;
use Tests\Traits\MakesTestVouchers;
use Throwable;

class IdentityProviderScimUserTest extends TestCase
{
    use DatabaseTransactions;
    use MakesTestIdentityProviders;
    use MakesTestFunds;
    use MakesTestOrganizations;
    use MakesTestVouchers;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        Config::set('identity_providers.enabled', true);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testScimAvailabilityIsIndependentFromSsoPauseAndScopedToConnection(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity(), [
            'allow_identity_providers' => true,
            'allow_identity_provider_requester_provisioning' => true,
        ]);

        $connection = $this->makeEntraConnection($organization);
        $token = Str::random(64);
        $this->makeIdentityProviderScimCredential($connection, $token);

        $otherOrganization = $this->makeTestOrganization($this->makeIdentity(), [
            'allow_identity_providers' => true,
            'allow_identity_provider_requester_provisioning' => true,
        ]);

        $otherConnection = $this->makeEntraConnection($otherOrganization);

        $this->apiGetIdentityProviderScimUsersRequest($otherConnection, $token)->assertUnauthorized();
        $this->apiGetIdentityProviderScimUsersRequest($connection, $token)->assertOk();

        $connection->update(['status' => IdentityProviderConnection::STATUS_PAUSED]);

        $this->apiGetIdentityProviderScimUsersRequest($connection, $token)->assertOk();

        $organization->forceFill(['allow_identity_provider_requester_provisioning' => false])->save();

        $this->apiGetIdentityProviderScimUsersRequest($connection, $token)->assertUnauthorized();

        $organization->forceFill(['allow_identity_provider_requester_provisioning' => true])->save();
        $connection->update(['status' => IdentityProviderConnection::STATUS_DISCONNECTED]);

        $this->apiGetIdentityProviderScimUsersRequest($connection, $token)->assertUnauthorized();
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testReadsAndFiltersExposeOnlyThisConnectionsLiveRequesters(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity(), [
            'allow_identity_providers' => true,
            'allow_identity_provider_requester_provisioning' => true,
        ]);

        $connection = $this->makeEntraConnection($organization);
        $token = Str::random(64);
        $this->makeIdentityProviderScimCredential($connection, $token);

        $active = $this->makeIdentityProviderRequester($connection);

        $disabled = $this->makeIdentityProviderRequester($connection, [
            'provisioning_status' => IdentityProviderMembership::PROVISIONING_STATUS_DISABLED,
        ]);

        $deleted = $this->makeIdentityProviderRequester($connection, [
            'provisioning_status' => IdentityProviderMembership::PROVISIONING_STATUS_DELETED,
        ]);

        $employee = $this->makeIdentityProviderMembership($connection, $organization->addEmployee($this->makeIdentity()));
        $otherConnection = $this->makeEntraConnection($this->makeTestOrganization($this->makeIdentity()));
        $other = $this->makeIdentityProviderRequester($otherConnection);

        $this->apiGetIdentityProviderScimUsersRequest($connection, $token)
            ->assertOk()
            ->assertJsonPath('Resources.*.id', [$active->uid, $disabled->uid]);

        $this->apiGetIdentityProviderScimUsersRequest($connection, $token, $active->uid)
            ->assertOk()
            ->assertJsonPath('userName', $active->scim_user_name)
            ->assertJsonPath('emails.0.value', $active->identity->email);

        $this->apiGetIdentityProviderScimUsersRequest($connection, $token, $disabled->uid)
            ->assertOk()->assertJsonPath('active', false);

        foreach ([$deleted, $employee, $other] as $hidden) {
            $this->apiGetIdentityProviderScimUsersRequest($connection, $token, $hidden->uid)->assertNotFound();
        }

        $this->apiGetIdentityProviderScimUsersRequest($connection, $token, query: [
            'filter' => 'externalId eq "' . $active->external_identity->object_id . '"',
        ])->assertOk()->assertJsonPath('Resources.*.id', [$active->uid]);

        $this->apiGetIdentityProviderScimUsersRequest($connection, $token, query: [
            'filter' => 'userName eq "' . strtoupper($disabled->scim_user_name) . '"',
        ])->assertOk()->assertJsonPath('Resources.*.id', [$disabled->uid]);

        $this->apiGetIdentityProviderScimUsersRequest($connection, $token, query: [
            'filter' => 'userName eq "' . $active->identity->email . '"',
        ])->assertOk()->assertJsonPath('Resources', []);

        $this->apiGetIdentityProviderScimUsersRequest($connection, $token, query: [
            'filter' => 'externalId eq "' . $other->external_identity->object_id . '"',
        ])->assertOk()->assertJsonPath('Resources', []);
    }

    /**
     * @return void
     */
    public function testCreateBuildsARequesterWithUsernameAndVerifiedContactEmail(): void
    {
        [$connection, $token] = $this->makeIdentityProviderScimContext();
        $payload = $this->makeIdentityProviderScimUserPayload([
            'schemas' => [
                IdentityProviderScimDiscovery::SCHEMA_USER, IdentityProviderScimDiscovery::SCHEMA_ENTERPRISE_USER,
            ],
        ]);
        $membership = $this->provisionIdentityProviderRequester($connection, $token, $payload);
        $identity = $membership->identity;

        $this->assertTrue($membership->isRequester());
        $this->assertTrue($membership->isClaimed());
        $this->assertSame($payload['userName'], $membership->scim_user_name);
        $this->assertSame($payload['emails'][0]['value'], $identity->email);
        $this->assertTrue($identity->primary_email->verified);
        $this->assertSame($connection->tenant_id, $membership->external_identity->tenant_id);
        $this->assertSame($identity->id, $membership->external_identity->identity_id);
        $this->assertNull($membership->employee_id);
        $this->assertFalse($identity->employees()->exists());
        $this->assertFalse($identity->proxies()->exists());

        $profile = $identity->profiles()->where('organization_id', $connection->organization_id)->firstOrFail();
        $records = $profile->profile_records()->get();

        $this->assertSame([ProfileRecord::SOURCE_ENTRA], $records->pluck('source')->unique()->values()->all());

        $this->apiGetIdentityProviderScimUsersRequest($connection, $token, $membership->uid)
            ->assertOk()->assertJsonPath('externalId', $payload['externalId'])->assertJsonPath('name', $payload['name']);
    }

    /**
     * @return void
     */
    public function testInvalidCreateLogsValidationRulesWithMatchingDiagnosticReference(): void
    {
        [$connection, $token] = $this->makeIdentityProviderScimContext();
        Log::spy();
        Log::shouldReceive('channel')->andReturnSelf();

        $response = $this->apiIdentityProviderScimUsersRequest(
            'POST',
            $connection,
            $token,
            $this->makeIdentityProviderScimUserPayload([
                'externalId' => 'private-invalid-object-id',
                'emails' => [['value' => 'private-invalid-email', 'type' => 'work']],
            ]),
        )->assertBadRequest()->assertJsonPath('scimType', 'invalidValue');

        $diagnosticId = $response->headers->get('X-Diagnostic-Id');
        $this->assertNotEmpty($diagnosticId);
        $this->assertFalse($connection->memberships()->exists());

        Log::shouldHaveReceived('warning')->once()->withArgs(function (string $message, array $context) use (
            $connection,
            $diagnosticId,
        ): bool {
            $this->assertSame('Entra protocol request rejected.', $message);
            $this->assertSame([
                'diagnostic_id' => $diagnosticId,
                'operation' => 'scim_request',
                'error_code' => 'scim_request_failed',
                'connection_id' => $connection->id,
                'organization_id' => $connection->organization_id,
                'http_status' => 400,
                'validation_errors' => ['external_id' => ['Uuid'], 'email' => ['Email']],
            ], $context);

            return true;
        });
    }

    /**
     * @return void
     */
    public function testProvisioningRejectsExistingPrimaryOrUnverifiedSecondaryEmail(): void
    {
        [$connection, $token] = $this->makeIdentityProviderScimContext();
        $existing = $this->makeIdentity($this->makeUniqueEmail());
        $existing->primary_email->update(['verified' => true]);
        $secondary = $existing->addEmail($this->makeUniqueEmail());
        $identities = Identity::count();
        $profiles = Profile::count();

        foreach ([$existing->email, $secondary->email] as $email) {
            $payload = $this->makeIdentityProviderScimUserPayload([
                'emails' => [['value' => $email, 'type' => 'work']],
            ]);

            $this->apiIdentityProviderScimUsersRequest('POST', $connection, $token, $payload)
                ->assertConflict()->assertJsonPath('scimType', 'uniqueness');

            $this->assertSame($identities, Identity::count());
            $this->assertSame($profiles, Profile::count());
            $this->assertFalse($connection->memberships()->exists());
            $this->assertFalse($existing->identity_provider_memberships()->exists());
        }

        $this->assertFalse($secondary->refresh()->verified);
    }

    /**
     * @return void
     */
    public function testDuplicateObjectsAndUsernamesDoNotOverwriteRequesterOrEmployeeLinks(): void
    {
        [$connection, $token] = $this->makeIdentityProviderScimContext();
        $payload = $this->makeIdentityProviderScimUserPayload();
        $membership = $this->provisionIdentityProviderRequester($connection, $token, $payload);
        $employee = $this->makeIdentityProviderMembership(
            $connection,
            $connection->organization->addEmployee($this->makeIdentity()),
        );

        $identities = Identity::count();

        foreach ([
            ['externalId' => $payload['externalId']],
            ['userName' => strtoupper($payload['userName'])],
            ['externalId' => $employee->external_identity->object_id],
        ] as $conflict) {
            $this->apiIdentityProviderScimUsersRequest(
                'POST',
                $connection,
                $token,
                $this->makeIdentityProviderScimUserPayload($conflict),
            )->assertConflict()->assertJsonPath('scimType', 'uniqueness');
        }

        $this->assertSame($identities, Identity::count());
        $this->assertSame(2, $connection->memberships()->count());
        $this->assertSame($payload['emails'][0]['value'], $membership->refresh()->identity->email);
        $this->assertFalse($employee->refresh()->isRequester());
        $this->assertNotNull($employee->employee_id);
    }

    /**
     * @return void
     */
    public function testEmailReplacementPreservesIdentityAndEmailRowWithPrivateChangeHistory(): void
    {
        [$connection, $token] = $this->makeIdentityProviderScimContext();
        $payload = $this->makeIdentityProviderScimUserPayload();
        $membership = $this->provisionIdentityProviderRequester($connection, $token, $payload);
        $identity = $membership->identity;
        $emailId = $identity->primary_email->id;
        $identity->addRecords(['primary_email' => $identity->email]);

        $newEmail = $this->makeUniqueEmail();
        $updated = [...$payload, 'userName' => $this->makeUniqueEmail('renamed-')];
        $updated['emails'][0]['value'] = $newEmail;

        $this->apiIdentityProviderScimUsersRequest('PUT', $connection, $token, $updated, $membership->uid)
            ->assertOk()->assertJsonPath('userName', $updated['userName'])->assertJsonPath('emails.0.value', $newEmail);

        $this->assertSame($identity->id, $membership->refresh()->identity_id);
        $this->assertSame($emailId, $identity->refresh()->primary_email->id);
        $this->assertTrue($identity->primary_email->verified);
        $this->assertSame($newEmail, $identity->records()->whereRelation('record_type', 'key', 'primary_email')->value('value'));

        /** @var EventLog $event */
        $event = $connection->logs()->where('event', IdentityProviderConnection::EVENT_SCIM_USER_EMAIL_CHANGED)->firstOrFail();

        $this->assertEquals(['old' => $payload['emails'][0]['value'], 'new' => $newEmail], $event->data['context']['email_change']);

        $response = $this->apiGetIdentityProviderConnectionEventsRequest(
            $connection->organization,
            $connection,
            ['category' => 'requester_provisioning'],
            $connection->organization->identity,
        )->assertOk();

        /** @var array<string, mixed> $publicEvent */
        $publicEvent = collect($response->json('data'))->sole('id', $event->id);

        $this->assertArrayNotHasKey('context', $publicEvent);
        $this->assertStringNotContainsString($payload['emails'][0]['value'], json_encode($publicEvent));
    }

    /**
     * @return void
     */
    public function testPatchPreservesOmittedNameAndPutClearsNamesWithoutRemovingHistory(): void
    {
        [$connection, $token] = $this->makeIdentityProviderScimContext();
        $payload = $this->makeIdentityProviderScimUserPayload();
        $membership = $this->provisionIdentityProviderRequester($connection, $token, $payload);

        $this->apiIdentityProviderScimUsersRequest('PATCH', $connection, $token, [
            'schemas' => [IdentityProviderScimUserPayload::SCHEMA_PATCH],
            'Operations' => [['op' => 'replace', 'path' => 'name.givenName', 'value' => 'Janet']],
        ], $membership->uid)->assertOk()->assertJsonPath('name', ['givenName' => 'Janet', 'familyName' => 'Doe']);

        unset($payload['name']);

        $this->apiIdentityProviderScimUsersRequest('PUT', $connection, $token, $payload, $membership->uid)
            ->assertOk()->assertJsonPath('name', ['givenName' => '', 'familyName' => '']);

        $this->apiViewIdentityRequest($connection->organization_id, $membership->identity_id, $connection->organization->identity)
            ->assertOk()
            ->assertJsonPath('data.records.given_name.*.value', ['', 'Janet', 'Jane'])
            ->assertJsonPath('data.records.family_name.*.value', ['', 'Doe'])
            ->assertJsonPath('data.records.given_name.*.source', array_fill(0, 3, ProfileRecord::SOURCE_ENTRA))
            ->assertJsonPath('data.records.family_name.*.source', array_fill(0, 2, ProfileRecord::SOURCE_ENTRA));
    }

    /**
     * @return void
     */
    public function testPatchUpdatesBothNamesFromPathlessEntraAttributes(): void
    {
        [$connection, $token] = $this->makeIdentityProviderScimContext();
        $membership = $this->provisionIdentityProviderRequester(
            $connection,
            $token,
            $this->makeIdentityProviderScimUserPayload(),
        );

        $this->apiIdentityProviderScimUsersRequest('PATCH', $connection, $token, [
            'schemas' => [IdentityProviderScimUserPayload::SCHEMA_PATCH],
            'Operations' => [['op' => 'replace', 'value' => [
                'name.givenName' => 'Janet',
                'name.familyName' => 'Smith',
            ]]],
        ], $membership->uid)
            ->assertOk()->assertJsonPath('name', ['givenName' => 'Janet', 'familyName' => 'Smith']);

        $this->apiGetIdentityProviderScimUsersRequest($connection, $token, $membership->uid)
            ->assertOk()->assertJsonPath('name', ['givenName' => 'Janet', 'familyName' => 'Smith']);
    }

    /**
     * @return void
     */
    public function testConflictingUpdateDoesNotPartiallyChangeOrDisableRequesterAndRetainsFailureAudit(): void
    {
        [$connection, $token] = $this->makeIdentityProviderScimContext();
        $payload = $this->makeIdentityProviderScimUserPayload();
        $membership = $this->provisionIdentityProviderRequester($connection, $token, $payload);
        $proxy = $this->makeIdentityProviderProxy($membership);
        $voucher = $this->makeTestVoucher($this->makeTestFund($connection->organization), $membership->identity, amount: 100);
        $existing = $this->makeIdentity($this->makeUniqueEmail());
        $profile = $membership->identity->profiles()->where('organization_id', $connection->organization_id)->firstOrFail();
        $records = $profile->profile_records()->get()->toArray();

        $response = $this->apiIdentityProviderScimUsersRequest('PATCH', $connection, $token, [
            'schemas' => [IdentityProviderScimUserPayload::SCHEMA_PATCH],
            'Operations' => [['op' => 'replace', 'value' => [
                'active' => false,
                'name' => ['givenName' => 'Private rejected name'],
                'emails' => [['value' => $existing->email, 'type' => 'work']],
            ]]],
        ], $membership->uid)->assertConflict()->assertJsonPath('scimType', 'uniqueness');

        $this->assertTrue($membership->refresh()->isProvisioningActive());
        $this->assertSame($payload['emails'][0]['value'], $membership->identity->email);
        $this->assertSame($records, $profile->profile_records()->get()->toArray());
        $this->assertSame(Voucher::STATE_ACTIVE, $voucher->refresh()->state);
        $this->assertIdentityProviderProxy($proxy->refresh(), $membership);

        /** @var EventLog $event */
        $event = $connection->logs()->where('event', IdentityProviderConnection::EVENT_SCIM_USER_UPDATE_FAILED)->firstOrFail();
        $context = $event->data['context'];

        $this->assertNotEmpty($response->headers->get('X-Diagnostic-Id'));
        $this->assertSame($response->headers->get('X-Diagnostic-Id'), $context['diagnostic_id']);
        $this->assertSame('email_conflict', $context['reason']);
        $this->assertSame('unchanged', $context['result']);
        $this->assertSame(['active', 'name', 'emails'], $context['requested_fields']);
        $this->assertArrayNotHasKey('payload', $context);
        $this->assertStringNotContainsString('Private rejected name', json_encode($event->data));
    }
}
