<?php

namespace Tests\Unit\Searches;

use App\Models\Organization;
use App\Models\PhysicalCard;
use App\Searches\PhysicalCardSearch;
use App\Traits\DoesTesting;
use Illuminate\Support\Carbon;;
use Random\RandomException;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestOrganizations;
use Tests\Traits\MakesTestPhysicalCardTypes;
use Tests\Traits\MakesTestVouchers;

class PhysicalCardSearchTest extends SearchTestCase
{
    use DoesTesting;
    use MakesTestFunds;
    use MakesTestVouchers;
    use MakesTestOrganizations;
    use MakesTestPhysicalCardTypes;

    /**
     * @return void
     */
    public function testQueryBuilds(): void
    {
        $search = new PhysicalCardSearch([], PhysicalCard::query());

        $this->assertQueryBuilds($search->query());
    }

    /**
     * @return void
     */
    public function testFiltersByQuery(): void
    {
        $cardPart1 = '10000000';
        $cardPart2 = '99999999';

        $organization = $this->makeTestOrganization($this->makeIdentity());

        $type1 = $this->makeTestPhysicalCardType($organization);
        $type2 = $this->makeTestPhysicalCardType($organization);

        $fund1 = $this->makeTestFund($organization);
        $fund2 = $this->makeTestFund($organization);
        $voucher1 = $this->makeTestVoucher($fund1, $this->makeIdentity($this->makeUniqueEmail()), amount: 100);
        $voucher2 = $this->makeTestVoucher($fund2, $this->makeIdentity($this->makeUniqueEmail()), amount: 100);

        // assign physical cards to vouchers
        $card1 = $voucher1->addPhysicalCard("{$cardPart1}88888888", $type1);
        $card2 = $voucher2->addPhysicalCard("{$cardPart2}88888888", $type2);

        // match code
        $this->assertSearchIds(['q' => $cardPart1], [$card1->id], $organization);
        $this->assertSearchIds(['q' => $cardPart2], [$card2->id], $organization);
    }

    /**
     * @throws RandomException
     * @return void
     */
    public function testFiltersByFundId(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());

        $type1 = $this->makeTestPhysicalCardType($organization);
        $type2 = $this->makeTestPhysicalCardType($organization);

        $fund1 = $this->makeTestFund($organization);
        $fund2 = $this->makeTestFund($organization);
        $voucher1 = $this->makeTestVoucher($fund1, $this->makeIdentity($this->makeUniqueEmail()), amount: 100);
        $voucher2 = $this->makeTestVoucher($fund2, $this->makeIdentity($this->makeUniqueEmail()), amount: 100);

        // assign physical cards to vouchers
        $card1 = $voucher1->addPhysicalCard(random_int(1000000000000000, 9999999999999999), $type1);
        $card2 = $voucher2->addPhysicalCard(random_int(1000000000000000, 9999999999999999), $type2);

        $this->assertSearchIds(['fund_id' => null], [$card1->id, $card2->id], $organization);
        $this->assertSearchIds(['fund_id' => $fund1->id], [$card1->id], $organization);
        $this->assertSearchIds(['fund_id' => $fund2->id], [$card2->id], $organization);
    }

    /**
     * @throws RandomException
     * @return void
     */
    public function testFiltersByPhysicalCardTypeId(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());

        $type1 = $this->makeTestPhysicalCardType($organization);
        $type2 = $this->makeTestPhysicalCardType($organization);

        $fund1 = $this->makeTestFund($organization);
        $fund2 = $this->makeTestFund($organization);
        $voucher1 = $this->makeTestVoucher($fund1, $this->makeIdentity($this->makeUniqueEmail()), amount: 100);
        $voucher2 = $this->makeTestVoucher($fund2, $this->makeIdentity($this->makeUniqueEmail()), amount: 100);

        // assign physical cards to vouchers
        $card1 = $voucher1->addPhysicalCard(random_int(1000000000000000, 9999999999999999), $type1);
        $card2 = $voucher2->addPhysicalCard(random_int(1000000000000000, 9999999999999999), $type2);

        $this->assertSearchIds(['physical_card_type_id' => null], [$card1->id, $card2->id], $organization);
        $this->assertSearchIds(['physical_card_type_id' => $type1->id], [$card1->id], $organization);
        $this->assertSearchIds(['physical_card_type_id' => $type2->id], [$card2->id], $organization);
    }

    /**
     * @throws RandomException
     * @return void
     */
    public function testOrdersByCreatedAt(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());

        $type1 = $this->makeTestPhysicalCardType($organization);
        $type2 = $this->makeTestPhysicalCardType($organization);

        $fund1 = $this->makeTestFund($organization);
        $fund2 = $this->makeTestFund($organization);
        $voucher1 = $this->makeTestVoucher($fund1, $this->makeIdentity($this->makeUniqueEmail()), amount: 100);
        $voucher2 = $this->makeTestVoucher($fund2, $this->makeIdentity($this->makeUniqueEmail()), amount: 100);

        // assign physical cards to vouchers
        $olderCard = $voucher1->addPhysicalCard(random_int(1000000000000000, 9999999999999999), $type1);

        Carbon::setTestNow(now()->addDays(5));
        $newerCard = $voucher2->addPhysicalCard(random_int(1000000000000000, 9999999999999999), $type2);

        $this->assertSearchOrder([
            'order_by' => 'created_at',
            'order_dir' => 'asc',
        ], [$olderCard->id, $newerCard->id], $organization);

        $this->assertSearchOrder([
            'order_by' => 'created_at',
            'order_dir' => 'desc',
        ], [$newerCard->id, $olderCard->id], $organization);
    }

    /**
     * @param array $filters
     * @param Organization $organization
     * @return PhysicalCardSearch
     */
    private function makeSearch(array $filters, Organization $organization): PhysicalCardSearch
    {
        return new PhysicalCardSearch(
            $filters,
            PhysicalCard::whereRelation('voucher.fund', 'organization_id', $organization->id),
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
