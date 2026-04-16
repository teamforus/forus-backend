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
        $addressPart1 = 'match';
        $addressPart2 = 'other';

        $organization = $this->makeTestProviderOrganization($this->makeIdentity());

        $office1 = $this->makeOrganizationOffice($organization, ['address' => "$addressPart1 address"]);
        $office2 = $this->makeOrganizationOffice($organization, ['address' => "$addressPart2 address"]);
        $this->makeOrganizationOffice($organization, ['address' => 'Missing']);

        $this->assertSearchIds(['q' => $addressPart1], [$office1->id]);
        $this->assertSearchIds(['q' => $addressPart2], [$office2->id]);
    }

    /**
     * @return void
     */
    public function testFiltersByQueryMatchesOfficeByOrganizationName(): void
    {
        $namePart1 = 'match';
        $namePart2 = 'other';

        $organization1 = $this->makeTestProviderOrganization($this->makeIdentity(), ['name' => "$namePart1 name"]);
        $office1 = $this->makeOrganizationOffice($organization1, ['address' => 'address']);

        $organization2 = $this->makeTestProviderOrganization($this->makeIdentity(), ['name' => "$namePart2 name"]);
        $office2 = $this->makeOrganizationOffice($organization2, ['address' => 'address']);

        $this->assertSearchIds(['q' => $namePart1], [$office1->id]);
        $this->assertSearchIds(['q' => $namePart2], [$office2->id]);
    }

    /**
     * @return void
     */
    public function testFiltersByQueryMatchesOfficeByBusinessTypeName(): void
    {
        $typePart1 = 'match';
        $typePart2 = 'other';

        $organization1 = $this->makeTestProviderOrganization($this->makeIdentity(), [
            'business_type_id' => $this->makeTestBusinessType("$typePart1 business type")->id,
        ]);

        $office1 = $this->makeOrganizationOffice($organization1, ['address' => 'address']);

        $organization2 = $this->makeTestProviderOrganization($this->makeIdentity(), [
            'business_type_id' => $this->makeTestBusinessType("$typePart2 business type")->id,
        ]);

        $office2 = $this->makeOrganizationOffice($organization2, ['address' => 'address']);

        $this->assertSearchIds(['q' => $typePart1], [$office1->id]);
        $this->assertSearchIds(['q' => $typePart2], [$office2->id]);
    }

    /**
     * @return void
     */
    public function testFiltersByQueryMatchesOfficeByOrganizationContact(): void
    {
        $emailPart1 = 'something_un';
        $emailPart2 = 'any_un';

        $phonePart1 = '22233';
        $phonePart2 = '55566';

        $websitePart1 = 'forus';
        $websitePart2 = 'dashboard';

        $organization1 = $this->makeTestProviderOrganization($this->makeIdentity(), [
            'email' => $this->makeUniqueEmail($emailPart1),
            'email_public' => true,
            'phone' => "{$phonePart1}444",
            'phone_public' => true,
            'website' => "https://$websitePart1.example.com",
            'website_public' => true,
        ]);

        $organization2 = $this->makeTestProviderOrganization($this->makeIdentity(), [
            'email' => $this->makeUniqueEmail($emailPart2),
            'email_public' => true,
            'phone' => "{$phonePart2}444",
            'phone_public' => true,
            'website' => "https://$websitePart2.example.com",
            'website_public' => true,
        ]);

        $office1 = $this->makeOrganizationOffice($organization1);
        $office2 = $this->makeOrganizationOffice($organization2);

        $this->assertSearchIds(['q' => $emailPart1], [$office1->id]);
        $this->assertSearchIds(['q' => $emailPart2], [$office2->id]);

        $this->assertSearchIds(['q' => $phonePart1], [$office1->id]);
        $this->assertSearchIds(['q' => $phonePart2], [$office2->id]);

        $this->assertSearchIds(['q' => $websitePart1], [$office1->id]);
        $this->assertSearchIds(['q' => $websitePart2], [$office2->id]);
    }

    /**
     * @return void
     */
    public function testFiltersByQueryMatchesOfficeBranch(): void
    {
        $branchIdPart1 = '1111';
        $branchIdPart2 = '3333';

        $branchNamePart1 = 'match';
        $branchNamePart2 = 'unique';

        $branchNumberPart1 = '4444';
        $branchNumberPart2 = '5555';

        $organization1 = $this->makeTestProviderOrganization($this->makeIdentity());

        $office1 = $this->makeOrganizationOffice($organization1, [
            'address' => 'address',
            'branch_id' => "{$branchIdPart1}2222",
            'branch_name' => "$branchNamePart1 name",
            'branch_number' => "{$branchNumberPart1}777777",
        ]);

        $organization2 = $this->makeTestProviderOrganization($this->makeIdentity());

        $office2 = $this->makeOrganizationOffice($organization2, [
            'address' => 'address',
            'branch_id' => "{$branchIdPart2}2222",
            'branch_name' => "$branchNamePart2 name",
            'branch_number' => "{$branchNumberPart2}777777",
        ]);

        $this->assertSearchIds(['q' => $branchIdPart1], [$office1->id]);
        $this->assertSearchIds(['q' => $branchIdPart2], [$office2->id]);

        $this->assertSearchIds(['q' => $branchNamePart1], [$office1->id]);
        $this->assertSearchIds(['q' => $branchNamePart2], [$office2->id]);

        $this->assertSearchIds(['q' => $branchNumberPart1], [$office1->id]);
        $this->assertSearchIds(['q' => $branchNumberPart2], [$office2->id]);
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
