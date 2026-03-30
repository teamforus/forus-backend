<?php

namespace Tests\Unit\Searches;

use App\Models\Record;
use App\Models\RecordType;
use App\Searches\RecordSearch;
use App\Traits\DoesTesting;
use Tests\Traits\MakesTestFunds;

class RecordSearchTest extends SearchTestCase
{
    use DoesTesting;
    use MakesTestFunds;

    /**
     * @return void
     */
    public function testQueryBuilds(): void
    {
        $search = new RecordSearch([], Record::query());

        $this->assertQueryBuilds($search->query());
    }

    /**
     * @return void
     */
    public function testFiltersByRecordTypeAndCategory(): void
    {
        $identity = $this->makeIdentity();
        $organization = $this->makeTestOrganization($identity);
        $recordTypeA = $this->makeRecordType($organization, RecordType::TYPE_STRING, 'test_string_a');
        $recordTypeB = $this->makeRecordType($organization, RecordType::TYPE_STRING, 'test_string_b');

        $categoryA = $identity->createRecordCategory('test_category A');
        $recordA = $identity->makeRecord($recordTypeA, 'test', $categoryA->id);

        $categoryB = $identity->createRecordCategory('test_category B');
        $recordB = $identity->makeRecord($recordTypeB, 'test', $categoryB->id);

        $this->assertSearchIds(['type' => 'test_string_a'], [$recordA->id]);
        $this->assertSearchIds(['type' => 'test_string_b'], [$recordB->id]);

        $this->assertSearchIds(['record_category_id' => $categoryA->id], [$recordA->id]);
        $this->assertSearchIds(['record_category_id' => $categoryB->id], [$recordB->id]);
    }

    /**
     * @return void
     */
    public function testFiltersByHiddenSystemRecords(): void
    {
        $identity = $this->makeIdentity();
        $organization = $this->makeTestOrganization($identity);
        $recordTypeA = $this->makeRecordType($organization, RecordType::TYPE_STRING, 'test_string_a')->refresh();
        $recordTypeB = $this->makeRecordType($organization, RecordType::TYPE_STRING, 'test_string_b')->refresh();

        $this->assertTrue($recordTypeA->system);
        $this->assertTrue($recordTypeB->system);

        $recordA = $identity->makeRecord($recordTypeA, 'test');
        $recordB = $identity->makeRecord($recordTypeB, 'test');

        // assert records visible if hideSystemRecords is default (false)
        $this->assertSearchIds(['type' => 'test_string_a'], [$recordA->id]);
        $this->assertSearchIds(['type' => 'test_string_b'], [$recordB->id]);

        // assert records is hidden if hideSystemRecords is true
        $this->assertSearchIds(['type' => 'test_string_a'], [], true);
        $this->assertSearchIds(['type' => 'test_string_b'], [], true);
    }

    /**
     * @param array $filters
     * @param bool $hideSystemRecords
     * @return RecordSearch
     */
    private function makeSearch(array $filters, bool $hideSystemRecords = false): RecordSearch
    {
        return new RecordSearch($filters, Record::query(), $hideSystemRecords);
    }

    /**
     * @param array $filters
     * @param array $expectedIds
     * @param bool $hideSystemRecords
     * @return void
     */
    private function assertSearchIds(array $filters, array $expectedIds, bool $hideSystemRecords = false): void
    {
        $expected = collect($expectedIds)->sort()->values()->toArray();
        $search = $this->makeSearch($filters, $hideSystemRecords);
        $actual = collect($search->query()->pluck('id')->toArray())->sort()->values()->toArray();

        $this->assertSame($expected, $actual);
    }
}
