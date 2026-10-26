<?php

namespace Tests\Feature\Exports;

use App\Exports\IdentityProfilesExport;
use App\Models\Fund;
use App\Models\Identity;
use App\Models\Organization;
use App\Models\Permission;
use App\Services\IdentityProviderService\Models\IdentityProviderMembership;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Arr;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;
use Tests\Traits\BaseExport;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestIdentityProviders;
use Tests\Traits\MakesTestOrganizations;
use Tests\Traits\MakesTestVouchers;
use Throwable;

class IdentityProfilesExportTest extends TestCase
{
    use BaseExport;
    use MakesTestFunds;
    use MakesTestVouchers;
    use DatabaseTransactions;
    use MakesTestOrganizations;
    use MakesTestIdentityProviders;

    /**
     * @var string
     */
    protected string $apiExportUrl = '/api/v1/platform/organizations/%s/sponsor/identities/export';

    /**
     * @throws Throwable
     * @return void
     */
    public function testIdentityProfilesExport(): void
    {
        $identity = $this->makeIdentity($this->makeUniqueEmail());
        $organization = $this->makeTestOrganization($identity);
        $fund = $this->makeTestFund($organization);
        $this->makeTestVoucher($fund, $identity);

        $apiHeaders = $this->makeApiHeaders($this->makeIdentityProxy($organization->identity));

        // Assert export without fields - must be all fields by default
        $response = $this->getJson(
            sprintf($this->apiExportUrl, $organization->id, $fund->id) . '?data_format=csv',
            $apiHeaders
        );

        $fields = Arr::pluck(IdentityProfilesExport::getExportFields($organization), 'name');
        $this->assertExportedData($response, $identity, $fields);

        // Assert with passed all fields
        $url = sprintf($this->apiExportUrl, $organization->id, $fund->id) . '?' . http_build_query([
            'data_format' => 'csv',
            'fields' => Arr::pluck(IdentityProfilesExport::getExportFields($organization), 'key'),
        ]);

        $response = $this->getJson($url, $apiHeaders);
        $this->assertExportedData($response, $identity, $fields);

        // Assert specific fields
        $url = sprintf($this->apiExportUrl, $organization->id, $fund->id) . '?' . http_build_query([
            'data_format' => 'csv',
            'fields' => ['id', 'given_name', 'family_name', 'email'],
        ]);

        $response = $this->getJson($url, $apiHeaders);

        $this->assertExportedData($response, $identity, [
            IdentityProfilesExport::trans('id'),
            IdentityProfilesExport::trans('given_name'),
            IdentityProfilesExport::trans('family_name'),
            IdentityProfilesExport::trans('email'),
        ]);
    }

    /**
     * @return void
     */
    public function testTypeAndProvisioningFiltersMatchProfileListAndCsvExport(): void
    {
        [$sponsor, $viewer, $fund, $identities] = $this->makeProfileExportContext();

        foreach ([
            [[], array_values($identities)],
            [['type' => Identity::TYPE_VOUCHER], [$identities['active'], $identities['disabled'], $identities['deleted']]],
            [['type' => Identity::TYPE_PROFILE], [$identities['ordinary']]],
            [['type' => Identity::TYPE_EMPLOYEE], [$identities['employee']]],
            [['type' => Identity::TYPE_EMPLOYEE, 'identity_provider_status' => 'disabled'], []],
            [['identity_provider_status' => 'managed'], [
                $identities['active'], $identities['disabled'], $identities['deleted'], $identities['employee'],
            ]],
            [['identity_provider_status' => 'active'], [$identities['active'], $identities['employee']]],
            [['identity_provider_status' => 'disabled'], [$identities['disabled'], $identities['deleted']]],
            [['identity_provider_status' => 'unmanaged'], [$identities['ordinary'], $identities['other']]],
            [['fund_id' => $fund->id], [$identities['ordinary'], $identities['other']]],
            [['fund_id' => $fund->id, 'identity_provider_status' => 'managed'], []],
        ] as [$query, $expected]) {
            $this->assertListAndExportContainIdentities($sponsor, $viewer, $query, $expected);
        }

        $sponsor->forceFill(['allow_identity_provider_requester_provisioning' => false])->save();

        $this->assertListAndExportContainIdentities($sponsor, $viewer, [
            'identity_provider_status' => 'active',
        ], [$identities['active'], $identities['employee']]);

        $sponsor->forceFill(['allow_identity_provider_employee_provisioning' => false])->save();

        $filter = ['identity_provider_status' => 'managed'];

        $this->apiListIdentitiesRequest($sponsor->id, $viewer, $filter)
            ->assertJsonValidationErrors('identity_provider_status');

        $this->apiExportIdentitiesRequest($sponsor->id, $viewer, $filter)
            ->assertJsonValidationErrors('identity_provider_status');

        $this->assertListAndExportContainIdentities($sponsor, $viewer, [], array_values($identities));

        $this->assertListAndExportContainIdentities($sponsor, $viewer, [
            'type' => Identity::TYPE_EMPLOYEE,
        ], [$identities['employee']]);
    }

    /**
     * @return void
     */
    public function testTypeAndProvisioningColumnsRespectOrganizationVisibility(): void
    {
        [$sponsor, $viewer, , $identities] = $this->makeProfileExportContext();
        $fields = ['type', 'identity_provider_status', 'email'];

        $this->apiGetIdentitiesExportFieldsRequest($sponsor->id, $viewer)->assertOk()
            ->assertJsonFragment(['key' => 'type', 'name' => 'Type'])
            ->assertJsonFragment(['key' => 'identity_provider_status', 'name' => 'Entra-beheer']);

        $rows = $this->assertCsvExportResponse($this->apiExportIdentitiesRequest($sponsor->id, $viewer, [
            'fields' => $fields,
        ]));

        $this->assertExportHeaders($rows, ['Type', 'Entra-beheer', IdentityProfilesExport::trans('email')]);

        $this->assertEqualsCanonicalizing([
            ['Standaard', 'Actief', $identities['active']->email],
            ['Standaard', 'Uitgeschakeld', $identities['disabled']->email],
            ['Standaard', 'Uitgeschakeld', $identities['deleted']->email],
            ['Medewerker', 'Actief', $identities['employee']->email],
            ['Handmatig', '-', $identities['ordinary']->email],
            ['-', '-', $identities['other']->email],
        ], array_slice($rows, 1));

        $sponsor->forceFill([
            'allow_identity_provider_employee_provisioning' => false,
            'allow_identity_provider_requester_provisioning' => false,
        ])->save();

        $this->apiGetIdentitiesExportFieldsRequest($sponsor->id, $viewer)->assertOk()
            ->assertJsonFragment(['key' => 'type', 'name' => 'Type'])
            ->assertJsonMissingExact(['key' => 'identity_provider_status', 'name' => 'Entra-beheer']);

        $this->apiExportIdentitiesRequest($sponsor->id, $viewer, ['fields' => $fields])
            ->assertJsonValidationErrors('fields.1');
    }

    /**
     * @return array{Organization, Identity, Fund, array<string, Identity>}
     */
    protected function makeProfileExportContext(): array
    {
        $sponsor = $this->makeTestOrganization($this->makeIdentity(), [
            'allow_identity_provider_requester_provisioning' => true,
            'allow_identity_provider_employee_provisioning' => true,
            'allow_identity_provider_employee_provisioning_profiles' => true,
        ]);

        $viewer = $this->makeTestEmployeeWithPermissions($sponsor, [Permission::VIEW_IDENTITIES]);
        $connection = $this->makeEntraConnection($sponsor);
        $active = $this->makeIdentityProviderRequester($connection)->identity;
        $employee = $this->makeIdentityProviderEmployee($connection)->identity;
        $employee->update(['type' => Identity::TYPE_EMPLOYEE, 'creator_organization_id' => $sponsor->id]);
        $employee->profiles()->create(['organization_id' => $sponsor->id]);
        $this->makeIdentityProviderEmployee($connection);

        $disabled = $this->makeIdentityProviderRequester($connection, [
            'provisioning_status' => IdentityProviderMembership::PROVISIONING_STATUS_DISABLED,
        ])->identity;

        $disabled->update(['type' => Identity::TYPE_VOUCHER]);

        $deleted = $this->makeIdentityProviderRequester($connection, [
            'provisioning_status' => IdentityProviderMembership::PROVISIONING_STATUS_DELETED,
        ])->identity;

        $ordinary = $this->makeIdentity($this->makeUniqueEmail());
        $ordinary->update(['type' => Identity::TYPE_PROFILE, 'creator_organization_id' => $sponsor->id]);
        $otherConnection = $this->makeEntraConnection($this->makeTestOrganization($this->makeIdentity()));
        $other = $this->makeIdentityProviderRequester($otherConnection)->identity;
        $other->update(['type' => Identity::TYPE_EMPLOYEE, 'creator_organization_id' => $otherConnection->organization_id]);
        $fund = $this->makeTestFund($sponsor);
        $otherFund = $this->makeTestFund($sponsor);

        $this->makeTestVoucher($fund, $ordinary);
        $this->makeTestVoucher($fund, $other);
        $this->makeTestVoucher($otherFund, $active);

        return [$sponsor, $viewer->identity, $fund, [
            'active' => $active,
            'disabled' => $disabled,
            'deleted' => $deleted,
            'employee' => $employee,
            'ordinary' => $ordinary,
            'other' => $other,
        ]];
    }

    /**
     * @param Organization $organization
     * @param Identity $identity
     * @param array $query
     * @param Identity[] $expected
     * @return void
     */
    protected function assertListAndExportContainIdentities(
        Organization $organization,
        Identity $identity,
        array $query,
        array $expected,
    ): void {
        $response = $this->apiListIdentitiesRequest($organization->id, $identity, $query)->assertOk();

        $this->assertEqualsCanonicalizing(
            array_map(fn (Identity $item) => $item->id, $expected),
            $response->json('data.*.id'),
        );

        $rows = $this->assertCsvExportResponse($this->apiExportIdentitiesRequest($organization->id, $identity, [
            ...$query, 'fields' => ['email'],
        ]));

        $this->assertEqualsCanonicalizing($response->json('data.*.email'), array_column(array_slice($rows, 1), 0));
    }

    /**
     * @param TestResponse $response
     * @param Identity $identity
     * @param array $fields
     * @return void
     */
    protected function assertExportedData(
        TestResponse $response,
        Identity $identity,
        array $fields,
    ): void {
        $rows = $this->assertCsvExportResponse($response);

        $this->assertExportHeaders($rows, $fields);
        $this->assertSame($identity->email, array_combine($rows[0], $rows[1])[IdentityProfilesExport::trans('email')]);
    }
}
