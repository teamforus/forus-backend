<?php

namespace Tests\Unit\Searches;

use App\Models\RecordType;
use App\Models\Voucher;
use App\Models\VoucherRecord;
use App\Searches\VoucherRecordSearch;
use App\Traits\DoesTesting;
use Illuminate\Support\Carbon;;
use Tests\Traits\MakesTestFunds;

class VoucherRecordSearchTest extends SearchTestCase
{
    use DoesTesting;
    use MakesTestFunds;

    /**
     * @return void
     */
    public function testQueryBuilds(): void
    {
        $search = new VoucherRecordSearch([], VoucherRecord::query());

        $this->assertQueryBuilds($search->query());
    }

    /**
     * @return void
     */
    public function testFiltersByQuery(): void
    {
        $recordValuePart1 = 'match';
        $recordValuePart2 = 'other';

        $recordNotePart1 = 'primary';
        $recordNotePart2 = 'secondary';

        $recordTypeKeyPart1 = 'first';
        $recordTypeKeyPart2 = 'last';

        $identity = $this->makeIdentity();
        $organization = $this->makeTestOrganization($identity);

        $recordTypeA = $this->makeRecordType($organization, RecordType::TYPE_STRING, "test_string_$recordTypeKeyPart1");
        $recordTypeA->translateOrNew(app()->getLocale())->fill(['name' => $recordTypeA->key])->save();

        $recordTypeB = $this->makeRecordType($organization, RecordType::TYPE_STRING, "test_string_$recordTypeKeyPart2");
        $recordTypeB->translateOrNew(app()->getLocale())->fill(['name' => $recordTypeB->key])->save();

        $fund = $this->makeTestFund($organization);
        $voucher = $fund->makeVoucher($identity);

        $recordA = $voucher->appendRecord($recordTypeA->key, "$recordValuePart1 value", "$recordNotePart1 note");
        $recordB = $voucher->appendRecord($recordTypeB->key, "$recordValuePart2 value", "$recordNotePart2 note");

        $this->assertSearchIds(['q' => $recordValuePart1], [$recordA->id], $voucher);
        $this->assertSearchIds(['q' => $recordNotePart1], [$recordA->id], $voucher);
        $this->assertSearchIds(['q' => $recordTypeKeyPart1], [$recordA->id], $voucher);

        $this->assertSearchIds(['q' => $recordValuePart2], [$recordB->id], $voucher);
        $this->assertSearchIds(['q' => $recordNotePart2], [$recordB->id], $voucher);
        $this->assertSearchIds(['q' => $recordTypeKeyPart2], [$recordB->id], $voucher);
    }

    /**
     * @return void
     */
    public function testOrdersByCreatedAt(): void
    {
        $identity = $this->makeIdentity();
        $organization = $this->makeTestOrganization($identity);

        $recordTypeA = $this->makeRecordType($organization, RecordType::TYPE_STRING, 'A test_string');
        $recordTypeB = $this->makeRecordType($organization, RecordType::TYPE_STRING, 'B test_string');

        $fund = $this->makeTestFund($organization);
        $voucher = $fund->makeVoucher($identity);

        $olderRecord = $voucher->appendRecord($recordTypeA->key, 'value');

        Carbon::setTestNow(now()->addDays(5));
        $newerRecord = $voucher->appendRecord($recordTypeB->key, 'other value');

        // assert order by created at
        $this->assertSearchOrder([
            'order_by' => 'created_at',
            'order_dir' => 'asc',
        ], [$olderRecord->id, $newerRecord->id], $voucher);

        $this->assertSearchOrder([
            'order_by' => 'created_at',
            'order_dir' => 'desc',
        ], [$newerRecord->id, $olderRecord->id], $voucher);
    }

    /**
     * @return void
     */
    public function testOrdersById(): void
    {
        $identity = $this->makeIdentity();
        $organization = $this->makeTestOrganization($identity);

        $recordTypeA = $this->makeRecordType($organization, RecordType::TYPE_STRING, 'A test_string');
        $recordTypeB = $this->makeRecordType($organization, RecordType::TYPE_STRING, 'B test_string');

        $fund = $this->makeTestFund($organization);
        $voucher = $fund->makeVoucher($identity);

        $olderRecord = $voucher->appendRecord($recordTypeA->key, 'value');
        $newerRecord = $voucher->appendRecord($recordTypeB->key, 'other value');

        // assert order by ID
        $this->assertSearchOrder([
            'order_by' => 'id',
            'order_dir' => 'asc',
        ], [$olderRecord->id, $newerRecord->id], $voucher);

        $this->assertSearchOrder([
            'order_by' => 'id',
            'order_dir' => 'desc',
        ], [$newerRecord->id, $olderRecord->id], $voucher);
    }

    /**
     * @return void
     */
    public function testOrdersByRecordTypeName(): void
    {
        $identity = $this->makeIdentity();
        $organization = $this->makeTestOrganization($identity);

        $recordTypeA = $this->makeRecordType($organization, RecordType::TYPE_STRING, 'A test_string');
        $recordTypeA->translateOrNew(app()->getLocale())->fill(['name' => $recordTypeA->key])->save();

        $recordTypeB = $this->makeRecordType($organization, RecordType::TYPE_STRING, 'B test_string');
        $recordTypeB->translateOrNew(app()->getLocale())->fill(['name' => $recordTypeB->key])->save();

        $fund = $this->makeTestFund($organization);
        $voucher = $fund->makeVoucher($identity);

        $olderRecord = $voucher->appendRecord($recordTypeA->key, 'value');
        $newerRecord = $voucher->appendRecord($recordTypeB->key, 'other value');

        $this->assertSearchOrder([
            'order_by' => 'record_type_name',
            'order_dir' => 'asc',
        ], [$olderRecord->id, $newerRecord->id], $voucher);

        $this->assertSearchOrder([
            'order_by' => 'record_type_name',
            'order_dir' => 'desc',
        ], [$newerRecord->id, $olderRecord->id], $voucher);
    }

    /**
     * @return void
     */
    public function testOrdersByValue(): void
    {
        $identity = $this->makeIdentity();
        $organization = $this->makeTestOrganization($identity);

        $recordTypeA = $this->makeRecordType($organization, RecordType::TYPE_STRING, 'test_string_a');
        $recordTypeB = $this->makeRecordType($organization, RecordType::TYPE_STRING, 'test_string_b');

        $fund = $this->makeTestFund($organization);
        $voucher = $fund->makeVoucher($identity);

        $olderRecord = $voucher->appendRecord($recordTypeA->key, 'A value');
        $newerRecord = $voucher->appendRecord($recordTypeB->key, 'B value');

        $this->assertSearchOrder([
            'order_by' => 'value',
            'order_dir' => 'asc',
        ], [$olderRecord->id, $newerRecord->id], $voucher);

        $this->assertSearchOrder([
            'order_by' => 'value',
            'order_dir' => 'desc',
        ], [$newerRecord->id, $olderRecord->id], $voucher);
    }

    /**
     * @return void
     */
    public function testOrdersByNote(): void
    {
        $identity = $this->makeIdentity();
        $organization = $this->makeTestOrganization($identity);

        $recordTypeA = $this->makeRecordType($organization, RecordType::TYPE_STRING, 'test_string_a');
        $recordTypeB = $this->makeRecordType($organization, RecordType::TYPE_STRING, 'test_string_b');

        $fund = $this->makeTestFund($organization);
        $voucher = $fund->makeVoucher($identity);

        $olderRecord = $voucher->appendRecord($recordTypeA->key, 'value', 'A note');
        $newerRecord = $voucher->appendRecord($recordTypeB->key, 'value', 'B note');

        $this->assertSearchOrder([
            'order_by' => 'note',
            'order_dir' => 'asc',
        ], [$olderRecord->id, $newerRecord->id], $voucher);

        $this->assertSearchOrder([
            'order_by' => 'note',
            'order_dir' => 'desc',
        ], [$newerRecord->id, $olderRecord->id], $voucher);
    }

    /**
     * @param array $filters
     * @param Voucher $voucher
     * @return VoucherRecordSearch
     */
    private function makeSearch(array $filters, Voucher $voucher): VoucherRecordSearch
    {
        return new VoucherRecordSearch($filters, $voucher->voucher_records());
    }

    /**
     * @param array $filters
     * @param array $expectedIds
     * @param Voucher $voucher
     * @return void
     */
    private function assertSearchIds(array $filters, array $expectedIds, Voucher $voucher): void
    {
        $expected = collect($expectedIds)->sort()->values()->toArray();
        $search = $this->makeSearch($filters, $voucher);
        $actual = collect($search->query()->pluck('id')->toArray())->sort()->values()->toArray();

        $this->assertSame($expected, $actual);
    }

    /**
     * @param array $filters
     * @param array $expectedIds
     * @param Voucher $voucher
     * @return void
     */
    private function assertSearchOrder(array $filters, array $expectedIds, Voucher $voucher): void
    {
        $search = $this->makeSearch($filters, $voucher);
        $actual = $search->query()->pluck('id')->toArray();

        $this->assertSame($expectedIds, $actual);
    }
}
