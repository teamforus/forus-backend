<?php

namespace Tests\Feature\Exports;

use App\Exports\IdentityProfilesExport;
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
    public function testManagementFiltersMatchProfileListAndCsvExport(): void
    {
        $sponsor = $this->makeTestOrganization($this->makeIdentity(), [
            'allow_identity_provider_requester_provisioning' => true,
        ]);

        $viewer = $this->makeTestEmployeeWithPermissions($sponsor, [Permission::VIEW_IDENTITIES]);
        $connection = $this->makeEntraConnection($sponsor);
        $active = $this->makeIdentityProviderRequester($connection)->identity;

        $disabled = $this->makeIdentityProviderRequester($connection, [
            'provisioning_status' => IdentityProviderMembership::PROVISIONING_STATUS_DISABLED,
        ])->identity;

        $deleted = $this->makeIdentityProviderRequester($connection, [
            'provisioning_status' => IdentityProviderMembership::PROVISIONING_STATUS_DELETED,
        ])->identity;

        $ordinary = $this->makeIdentity($this->makeUniqueEmail());
        $otherConnection = $this->makeEntraConnection($this->makeTestOrganization($this->makeIdentity()));
        $other = $this->makeIdentityProviderRequester($otherConnection)->identity;
        $fund = $this->makeTestFund($sponsor);
        $otherFund = $this->makeTestFund($sponsor);

        $this->makeTestVoucher($fund, $ordinary);
        $this->makeTestVoucher($fund, $other);
        $this->makeTestVoucher($otherFund, $active);

        $identities = [$active, $disabled, $deleted, $ordinary, $other];

        foreach ([
            [[], $identities],
            [['identity_provider_status' => 'managed'], [$active, $disabled, $deleted]],
            [['identity_provider_status' => 'active'], [$active]],
            [['identity_provider_status' => 'disabled'], [$disabled, $deleted]],
            [['identity_provider_status' => 'unmanaged'], [$ordinary, $other]],
            [['fund_id' => $fund->id], [$ordinary, $other]],
            [['fund_id' => $fund->id, 'identity_provider_status' => 'managed'], []],
        ] as [$query, $expected]) {
            $this->assertListAndExportContainIdentities($sponsor, $viewer->identity, $query, $expected);
        }

        $sponsor->forceFill(['allow_identity_provider_requester_provisioning' => false])->save();
        $filter = ['identity_provider_status' => 'managed'];

        $this->apiListIdentitiesRequest($sponsor->id, $viewer->identity, $filter)
            ->assertJsonValidationErrors('identity_provider_status');

        $this->apiExportIdentitiesRequest($sponsor->id, $viewer->identity, $filter)
            ->assertJsonValidationErrors('identity_provider_status');

        $this->assertListAndExportContainIdentities($sponsor, $viewer->identity, [], $identities);
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
        $this->assertExportCell($rows, $identity->email, 3);
    }
}
