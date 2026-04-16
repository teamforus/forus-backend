<?php

namespace Tests\Unit\Searches;

use App\Models\Fund;
use App\Models\FundCriterion;
use App\Models\Organization;
use App\Models\Prevalidation;
use App\Models\RecordType;
use App\Searches\PrevalidationSearch;
use App\Traits\DoesTesting;
use Illuminate\Support\Carbon;;
use Illuminate\Support\Facades\Cache;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestOrganizations;

class PrevalidationSearchTest extends SearchTestCase
{
    use DoesTesting;
    use MakesTestFunds;
    use MakesTestOrganizations;

    /**
     * @return void
     */
    public function testQueryBuilds(): void
    {
        $search = new PrevalidationSearch([], Prevalidation::query());

        $this->assertQueryBuilds($search->query());
    }

    /**
     * @return void
     */
    public function testFiltersByQueryUid(): void
    {
        $uidPart1 = 'match';
        $uidPart2 = 'other';

        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization, fundConfigsData: ['csv_primary_key' => 'uid']);
        $this->addTestCriteriaToFund($fund);

        $prevalidation1 = $this->makePrevalidationForTestCriteria($organization, $fund, "{$uidPart1}_uid_key");
        $prevalidation2 = $this->makePrevalidationForTestCriteria($organization, $fund, "{$uidPart2}_uid_key");

        $this->assertSearchIds(['q' => $uidPart1], [$prevalidation1->id], $organization);
        $this->assertSearchIds(['q' => $uidPart2], [$prevalidation2->id], $organization);
    }

    /**
     * @return void
     */
    public function testFiltersByQueryRecordValue(): void
    {
        $recordValuePart1 = 'match';
        $recordValuePart2 = 'other';

        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization, fundConfigsData: ['csv_primary_key' => 'uid']);
        $recordTypeKey = $this->prepareTestCriteria($fund);
        $fund->refresh();

        // prepare first prevalidation by record value
        $response = $this->apiMakeStorePrevalidationRequest($organization, $fund, [
            $this->makeRequestCriterionValue($fund, $recordTypeKey, "{$recordValuePart1}_record_value"),
        ], [
            $fund->fund_config->csv_primary_key => token_generator()->generate(32),
        ]);

        $response->assertSuccessful();
        $prevalidation1 = Prevalidation::find($response->json('data.id'));

        // prepare second prevalidation by record value
        $response = $this->apiMakeStorePrevalidationRequest($organization, $fund, [
            $this->makeRequestCriterionValue($fund, $recordTypeKey, "{$recordValuePart2}_record_value"),
        ], [
            $fund->fund_config->csv_primary_key => token_generator()->generate(32),
        ]);

        $response->assertSuccessful();
        $prevalidation2 = Prevalidation::find($response->json('data.id'));

        $this->assertSearchIds(['q' => $recordValuePart1], [$prevalidation1->id], $organization);
        $this->assertSearchIds(['q' => $recordValuePart2], [$prevalidation2->id], $organization);
    }

    /**
     * @return void
     */
    public function testFiltersByFundId(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());

        $fund1 = $this->makeTestFund($organization, fundConfigsData: ['csv_primary_key' => 'uid']);
        $this->addTestCriteriaToFund($fund1);

        $fund2 = $this->makeTestFund($organization, fundConfigsData: ['csv_primary_key' => 'uid']);
        $this->addTestCriteriaToFund($fund2);

        $prevalidation1 = $this->makePrevalidationForTestCriteria($organization, $fund1);
        $prevalidation2 = $this->makePrevalidationForTestCriteria($organization, $fund2);

        $this->assertSearchIds(['fund_id' => $fund1->id], [$prevalidation1->id], $organization);
        $this->assertSearchIds(['fund_id' => $fund2->id], [$prevalidation2->id], $organization);
    }

    /**
     * @return void
     */
    public function testFiltersByState(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization, fundConfigsData: ['csv_primary_key' => 'uid']);
        $this->addTestCriteriaToFund($fund);

        $prevalidation1 = $this->makePrevalidationForTestCriteria($organization, $fund);
        $prevalidation2 = $this->makePrevalidationForTestCriteria($organization, $fund);

        $this->assertSearchIds(['state' => Prevalidation::STATE_PENDING], [$prevalidation1->id, $prevalidation2->id], $organization);
        $this->assertSearchIds(['state' => Prevalidation::STATE_USED], [], $organization);

        $prevalidation2->update(['state' => Prevalidation::STATE_USED]);
        $this->assertSearchIds(['state' => Prevalidation::STATE_USED], [$prevalidation2->id], $organization);
    }

    /**
     * @return void
     */
    public function testFiltersByExported(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization, fundConfigsData: ['csv_primary_key' => 'uid']);
        $this->addTestCriteriaToFund($fund);

        $prevalidation1 = $this->makePrevalidationForTestCriteria($organization, $fund);
        $prevalidation2 = $this->makePrevalidationForTestCriteria($organization, $fund);

        $this->assertSearchIds(['exported' => false], [$prevalidation1->id, $prevalidation2->id], $organization);
        $this->assertSearchIds(['exported' => true], [], $organization);

        $prevalidation2->update(['exported' => true]);
        $this->assertSearchIds(['exported' => true], [$prevalidation2->id], $organization);
    }

    /**
     * @return void
     */
    public function testFilterByCreatedAt(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization, fundConfigsData: ['csv_primary_key' => 'uid']);
        $this->addTestCriteriaToFund($fund);

        $prevalidation1 = $this->makePrevalidationForTestCriteria($organization, $fund);
        $prevalidation1->created_at = Carbon::now()->subDays(7);
        $prevalidation1->save();

        $prevalidation2 = $this->makePrevalidationForTestCriteria($organization, $fund);

        $this->assertSearchIds([
            'from' => Carbon::now()->subDays(8)->format('Y-m-d'),
            'to' => Carbon::now()->subDays(5)->format('Y-m-d'),
        ], [$prevalidation1->id], $organization);

        $this->assertSearchIds([
            'from' => Carbon::now()->subDays(5)->format('Y-m-d'),
            'to' => Carbon::now()->addDays(2)->format('Y-m-d'),
        ], [$prevalidation2->id], $organization);

        $this->assertSearchIds([
            'from' => Carbon::now()->subDays(5)->format('Y-m-d'),
        ], [$prevalidation2->id], $organization);

        $this->assertSearchIds([
            'to' => Carbon::now()->subDays(5)->format('Y-m-d'),
        ], [$prevalidation1->id], $organization);

        $this->assertSearchIds([
            'from' => Carbon::now()->subDays(10)->format('Y-m-d'),
            'to' => Carbon::now()->addDays(2)->format('Y-m-d'),
        ], [$prevalidation1->id, $prevalidation2->id], $organization);
    }

    /**
     * @return void
     */
    public function testOrdersByCreatedAt(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization, fundConfigsData: ['csv_primary_key' => 'uid']);
        $this->addTestCriteriaToFund($fund);

        $olderPrevalidation = $this->makePrevalidationForTestCriteria($organization, $fund);

        Carbon::setTestNow(now()->addDays(5));
        $newerPrevalidation = $this->makePrevalidationForTestCriteria($organization, $fund);

        $this->assertSearchOrder([
            'order_by' => 'created_at',
            'order_dir' => 'asc',
        ], [$olderPrevalidation->id, $newerPrevalidation->id], $organization);

        $this->assertSearchOrder([
            'order_by' => 'created_at',
            'order_dir' => 'desc',
        ], [$newerPrevalidation->id, $olderPrevalidation->id], $organization);
    }

    /**
     * @param Fund $fund
     * @return string
     */
    private function prepareTestCriteria(Fund $fund): string
    {
        $recordTypeKey = 'test_string_' . token_generator()->generate(32);

        $fund->criteria->each(function (FundCriterion $criterion) {
            $criterion->fund_criterion_rules()->delete();
            $criterion->fund_request_record()->delete();
        });

        $fund->criteria()->forceDelete();

        $this->makeRecordType($fund->organization, RecordType::TYPE_STRING, $recordTypeKey);

        $this->updateCriteriaRequest([
            $this->makeCriterion($recordTypeKey, null, '*', 5, 20),
        ], $fund)->assertSuccessful();

        Cache::flush();

        return $recordTypeKey;
    }

    /**
     * @param array $filters
     * @param Organization $organization
     * @return PrevalidationSearch
     */
    private function makeSearch(array $filters, Organization $organization): PrevalidationSearch
    {
        return new PrevalidationSearch(
            $filters,
            Prevalidation::where('organization_id', $organization->id)
        );
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
