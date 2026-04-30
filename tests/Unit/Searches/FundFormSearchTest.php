<?php

namespace Tests\Unit\Searches;

use App\Models\Fund;
use App\Models\FundForm;
use App\Models\Organization;
use App\Searches\FundFormSearch;
use App\Traits\DoesTesting;
use Carbon\Carbon;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestOrganizations;

class FundFormSearchTest extends SearchTestCase
{
    use DoesTesting;
    use MakesTestFunds;
    use MakesTestOrganizations;

    /**
     * @return void
     */
    public function testQueryBuilds(): void
    {
        $search = new FundFormSearch([], FundForm::query());

        $this->assertQueryBuilds($search->query());
    }

    /**
     * @return void
     */
    public function testFiltersByFundId(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund1 = $this->makeTestFund($organization);
        $fund2 = $this->makeTestFund($organization);

        $form1 = $this->createFundForm($fund1, 'Form 1');
        $this->createFundForm($fund2, 'Form 2');

        $this->assertSearchIds(['fund_id' => $fund1->id], [$form1->id], $organization);
    }

    /**
     * @return void
     */
    public function testFiltersByQueryMatchesFormAndFundName(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());

        $fundA = $this->makeTestFund($organization, ['name' => 'Match Fund']);
        $fundB = $this->makeTestFund($organization, ['name' => 'Other Fund']);
        $fundC = $this->makeTestFund($organization, ['name' => 'Missing Fund']);

        $formA = $this->createFundForm($fundA, 'Other Form');
        $formB = $this->createFundForm($fundB, 'Match Form');

        $this->createFundForm($fundC, 'Other Form 2');
        $this->assertSearchIds(['q' => 'Match'], [$formA->id, $formB->id], $organization);
    }

    /**
     * @return void
     */
    public function testFiltersByImplementationId(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund1 = $this->makeTestFund($organization);
        $implementation2 = $this->makeTestImplementation($organization);
        $fund2 = $this->makeTestFund($organization, implementation: $implementation2);

        $this->createFundForm($fund1, 'Form 1');
        $form2 = $this->createFundForm($fund2, 'Form 2');

        $this->assertSearchIds(['implementation_id' => $implementation2->id], [$form2->id], $organization);
    }

    /**
     * @return void
     */
    public function testFiltersByStateActive(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $activeFund = $this->makeTestFund($organization);
        $archivedFund = $this->makeTestFund($organization, ['end_date' => now()->subDay()]);

        $activeForm = $this->createFundForm($activeFund, 'Active Form');
        $this->createFundForm($archivedFund, 'Archived Form');

        $this->assertSearchIds(['state' => 'active'], [$activeForm->id], $organization);
    }

    /**
     * @return void
     */
    public function testFiltersByStateArchived(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $activeFund = $this->makeTestFund($organization);
        $archivedFund = $this->makeTestFund($organization, ['end_date' => now()->subDay()]);

        $this->createFundForm($activeFund, 'Active Form');
        $archivedForm = $this->createFundForm($archivedFund, 'Archived Form');

        $this->assertSearchIds(['state' => 'archived'], [$archivedForm->id], $organization);
    }

    /**
     * @return void
     */
    public function testOrdersByCreatedAt(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund1 = $this->makeTestFund($organization);
        $fund2 = $this->makeTestFund($organization);

        $olderForm = $this->createFundForm($fund1, 'Older Form', now()->subDay());
        $newerForm = $this->createFundForm($fund2, 'Newer Form', now());

        $this->assertSearchOrder([
            'order_by' => 'created_at',
            'order_dir' => 'asc',
        ], [$olderForm->id, $newerForm->id], $organization);

        $this->assertSearchOrder([
            'order_by' => 'created_at',
            'order_dir' => 'desc',
        ], [$newerForm->id, $olderForm->id], $organization);
    }

    /**
     * @param Fund $fund
     * @param string $name
     * @param Carbon|null $createdAt
     * @return FundForm
     */
    private function createFundForm(Fund $fund, string $name, Carbon $createdAt = null): FundForm
    {
        $form = $fund->fund_form()->create([
            'name' => $name,
        ]);

        if ($createdAt) {
            $form->forceFill([
                'created_at' => $createdAt,
            ])->save();
        }

        return $form;
    }

    /**
     * @param array $filters
     * @param Organization $organization
     * @return FundFormSearch
     */
    private function makeSearch(array $filters, Organization $organization): FundFormSearch
    {
        return new FundFormSearch($filters, FundForm::whereRelation('fund', 'organization_id', $organization->id));
    }

    /**
     * @param array $filters
     * @param array $expectedIds
     * @param Organization $organization
     * @return void
     */
    private function assertSearchIds(array $filters, array $expectedIds, Organization $organization): void
    {
        $expected = collect($expectedIds)->sort()->values()->toArray();
        $search = $this->makeSearch($filters, $organization);
        $actual = collect($search->query()->pluck('id')->toArray())->sort()->values()->toArray();

        $this->assertSame($expected, $actual);
    }

    /**
     * @param array $filters
     * @param array $expectedIds
     * @param Organization $organization
     * @return void
     */
    private function assertSearchOrder(array $filters, array $expectedIds, Organization $organization): void
    {
        $search = $this->makeSearch($filters, $organization);
        $actual = $search->query()->pluck('id')->toArray();

        $this->assertSame($expectedIds, $actual);
    }
}
