<?php

namespace Tests\Unit\Searches;

use App\Models\FundProvider;
use App\Models\Office;
use App\Searches\OfficeSearch;
use App\Traits\DoesTesting;
use Tests\Traits\MakesTestBusinessType;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestOrganizationOffices;
use Tests\Traits\MakesTestOrganizations;

class OfficeSearchTest extends SearchTestCase
{
    use DoesTesting;
    use MakesTestFunds;
    use MakesTestBusinessType;
    use MakesTestOrganizations;
    use MakesTestOrganizationOffices;

    /**
     * @return void
     */
    public function testQueryBuilds(): void
    {
        $search = new OfficeSearch([], Office::query());

        $this->assertQueryBuilds($search->query());
    }

    /**
     * @return void
     */
    public function testFiltersByOrganizationId(): void
    {
        $organization1 = $this->makeTestProviderOrganization($this->makeIdentity());
        $organization2 = $this->makeTestProviderOrganization($this->makeIdentity());
        $office1 = $this->makeOrganizationOffice($organization1);
        $office2 = $this->makeOrganizationOffice($organization2);

        $this->assertSearchIds(['organization_id' => $organization1->id], [$office1->id]);
        $this->assertSearchIds(['organization_id' => $organization2->id], [$office2->id]);
    }

    /**
     * @return void
     */
    public function testFiltersByQueryMatchesOfficeAddress(): void
    {
        $organization = $this->makeTestProviderOrganization($this->makeIdentity());

        $office1 = $this->makeOrganizationOffice($organization, ['address' => 'Match address']);
        $office2 = $this->makeOrganizationOffice($organization, ['address' => 'Other address']);
        $this->makeOrganizationOffice($organization, ['address' => 'Missing']);

        $this->assertSearchIds(['q' => 'Match'], [$office1->id]);
        $this->assertSearchIds(['q' => 'address'], [$office1->id, $office2->id]);
    }

    /**
     * @return void
     */
    public function testFiltersByQueryMatchesOfficeByOrganizationName(): void
    {
        $organization1 = $this->makeTestProviderOrganization($this->makeIdentity(), ['name' => 'Match organization']);
        $office1 = $this->makeOrganizationOffice($organization1, ['address' => 'address']);

        $organization2 = $this->makeTestProviderOrganization($this->makeIdentity(), ['name' => 'Other organization']);
        $office2 = $this->makeOrganizationOffice($organization2, ['address' => 'address']);

        $this->assertSearchIds(['q' => 'Match'], [$office1->id]);
        $this->assertSearchIds(['q' => 'organization'], [$office1->id, $office2->id]);
    }

    /**
     * @return void
     */
    public function testFiltersByQueryMatchesOfficeByBusinessTypeName(): void
    {
        $organization1 = $this->makeTestProviderOrganization($this->makeIdentity(), [
            'name' => 'organization',
            'business_type_id' => $this->makeTestBusinessType('Match business type')->id,
        ]);

        $office1 = $this->makeOrganizationOffice($organization1, ['address' => 'address']);

        $organization2 = $this->makeTestProviderOrganization($this->makeIdentity(), [
            'name' => 'organization',
            'business_type_id' => $this->makeTestBusinessType('Missed business type')->id,
        ]);

        $office2 = $this->makeOrganizationOffice($organization2, ['address' => 'address']);

        $this->assertSearchIds(['q' => 'Match'], [$office1->id]);
        $this->assertSearchIds(['q' => 'business type'], [$office1->id, $office2->id]);
    }

    /**
     * @return void
     */
    public function testFiltersByQueryMatchesOfficeByOrganizationContact(): void
    {
        $email1 = 'test@mail.com';
        $email2 = 'test@test.com';

        $phone1 = '222333444';
        $phone2 = '555666444';

        $website1 = 'https://forus.io';
        $website2 = 'https://forus.com';

        $organization1 = $this->makeTestProviderOrganization($this->makeIdentity(), [
            'name' => 'organization',
            'email' => $email1,
            'email_public' => true,
            'phone' => $phone1,
            'phone_public' => true,
            'website' => $website1,
            'website_public' => true,
        ]);

        $office1 = $this->makeOrganizationOffice($organization1, ['address' => 'address']);

        $organization2 = $this->makeTestProviderOrganization($this->makeIdentity(), [
            'name' => 'organization',
            'email' => $email2,
            'email_public' => true,
            'phone' => $phone2,
            'phone_public' => true,
            'website' => $website2,
            'website_public' => true,
        ]);

        $office2 = $this->makeOrganizationOffice($organization2, ['address' => 'address']);

        $this->assertSearchIds(['q' => '@mail.com'], [$office1->id]);
        $this->assertSearchIds(['q' => 'test@'], [$office1->id, $office2->id]);

        $this->assertSearchIds(['q' => '222333'], [$office1->id]);
        $this->assertSearchIds(['q' => '444'], [$office1->id, $office2->id]);

        $this->assertSearchIds(['q' => 'forus.io'], [$office1->id]);
        $this->assertSearchIds(['q' => 'https://forus'], [$office1->id, $office2->id]);
    }

    /**
     * @return void
     */
    public function testFiltersByQueryMatchesOfficeBranch(): void
    {
        $organization1 = $this->makeTestProviderOrganization($this->makeIdentity(), ['name' => 'organization']);

        $office1 = $this->makeOrganizationOffice($organization1, [
            'address' => 'address',
            'branch_id' => '11112222',
            'branch_name' => 'AAAADDDD',
            'branch_number' => '666666777777',
        ]);

        $organization2 = $this->makeTestProviderOrganization($this->makeIdentity(), ['name' => 'organization']);

        $office2 = $this->makeOrganizationOffice($organization2, [
            'address' => 'address',
            'branch_id' => '33332222',
            'branch_name' => 'BBBBDDDD',
            'branch_number' => '7777788888',
        ]);

        $this->assertSearchIds(['q' => '1111'], [$office1->id]);
        $this->assertSearchIds(['q' => '2222'], [$office1->id, $office2->id]);

        $this->assertSearchIds(['q' => 'AAAA'], [$office1->id]);
        $this->assertSearchIds(['q' => 'DDDD'], [$office1->id, $office2->id]);

        $this->assertSearchIds(['q' => '66666'], [$office1->id]);
        $this->assertSearchIds(['q' => '77777'], [$office1->id, $office2->id]);
    }

    /**
     * @return void
     */
    public function testFiltersByApproved(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization);

        $approvedProvider = $this->makeTestProviderOrganization($this->makeIdentity($this->makeUniqueEmail()));
        $this->makeTestFundProvider($approvedProvider, $fund);

        $rejectedProvider = $this->makeTestProviderOrganization($this->makeIdentity($this->makeUniqueEmail()));
        $this->makeTestFundProvider($rejectedProvider, $fund)->update(['state' => FundProvider::STATE_REJECTED]);

        $office1 = $this->makeOrganizationOffice($approvedProvider);
        $office2 = $this->makeOrganizationOffice($rejectedProvider);

        $this->assertSearchIds(['approved' => true, 'organization_id' => $approvedProvider->id], [$office1->id]);
        $this->assertSearchIds(['approved' => true, 'organization_id' => $rejectedProvider->id], []);
        $this->assertSearchIds(['approved' => false, 'organization_id' => $rejectedProvider->id], [$office2->id]);
    }

    /**
     * @param array $filters
     * @return OfficeSearch
     */
    private function makeSearch(array $filters): OfficeSearch
    {
        return new OfficeSearch($filters, Office::query(), true);
    }

    /**
     * @param array $filters
     * @param array $expectedIds
     * @return void
     */
    private function assertSearchIds(array $filters, array $expectedIds): void
    {
        $expected = collect($expectedIds)->sort()->values()->toArray();
        $search = $this->makeSearch($filters);
        $actual = collect($search->query()->pluck('id')->toArray())->sort()->values()->toArray();

        $this->assertSame($expected, $actual);
    }
}
