<?php

namespace Tests\Unit\Searches;

use App\Models\RecordType;
use App\Searches\RecordTypeSearch;
use App\Traits\DoesTesting;
use Tests\Traits\MakesTestFunds;

class RecordTypeSearchTest extends SearchTestCase
{
    use DoesTesting;
    use MakesTestFunds;

    /**
     * @return void
     */
    public function testQueryBuilds(): void
    {
        $search = new RecordTypeSearch([], RecordType::query());

        $this->assertQueryBuilds($search->query());
    }

    /**
     * @return void
     */
    public function testFiltersByOrganizationId(): void
    {
        $existing = RecordType::query()->whereNull('organization_id')->pluck('id')->toArray();

        $identity = $this->makeIdentity();
        $organizationA = $this->makeTestOrganization($identity);
        $organizationB = $this->makeTestOrganization($identity);

        $recordTypeA = $this->makeRecordType($organizationA, RecordType::TYPE_STRING, 'test_string_a');
        $recordTypeB = $this->makeRecordType($organizationB, RecordType::TYPE_STRING, 'test_string_b');

        $this->assertSearchIds(['organization_id' => $organizationA->id], [...$existing, $recordTypeA->id]);
        $this->assertSearchIds(['organization_id' => $organizationB->id], [...$existing, $recordTypeB->id]);
    }

    /**
     * @return void
     */
    public function testFiltersByFlags(): void
    {
        $existing = RecordType::query()->whereNull('organization_id')->get();

        $identity = $this->makeIdentity();
        $organizationA = $this->makeTestOrganization($identity);
        $organizationB = $this->makeTestOrganization($identity);

        $recordTypeA = $this->makeRecordType($organizationA, RecordType::TYPE_STRING, 'test_string_a');
        $recordTypeA->update(['vouchers' => true]);

        $recordTypeB = $this->makeRecordType($organizationB, RecordType::TYPE_STRING, 'test_string_b');
        $recordTypeB->update(['vouchers' => true]);

        $existingByVoucher = $existing->where('vouchers', true)->pluck('id')->toArray();

        // assert when vouchers is true
        $this->assertSearchIds([
            'organization_id' => $organizationA->id,
            'vouchers' => true,
        ], [...$existingByVoucher, $recordTypeA->id]);

        $this->assertSearchIds([
            'organization_id' => $organizationB->id,
            'vouchers' => true,
        ], [...$existingByVoucher, $recordTypeB->id]);

        // assert when criteria is true
        $recordTypeA->update(['criteria' => true]);
        $recordTypeB->update(['criteria' => true]);

        $existingByVoucher = $existing->where('criteria', true)->pluck('id')->toArray();

        $this->assertSearchIds([
            'organization_id' => $organizationA->id,
            'criteria' => true,
        ], [...$existingByVoucher, $recordTypeA->id]);

        $this->assertSearchIds([
            'organization_id' => $organizationB->id,
            'criteria' => true,
        ], [...$existingByVoucher, $recordTypeB->id]);

        // assert when without_system is true
        $recordTypeA->update(['system' => false]);
        $recordTypeB->update(['system' => false]);

        $existingByVoucher = $existing->where('system', false)->pluck('id')->toArray();

        $this->assertSearchIds([
            'organization_id' => $organizationA->id,
            'without_system' => true,
        ], [...$existingByVoucher, $recordTypeA->id]);

        $this->assertSearchIds([
            'organization_id' => $organizationB->id,
            'without_system' => true,
        ], [...$existingByVoucher, $recordTypeB->id]);
    }

    /**
     * @param array $filters
     * @return RecordTypeSearch
     */
    private function makeSearch(array $filters): RecordTypeSearch
    {
        return new RecordTypeSearch($filters, RecordType::query());
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
