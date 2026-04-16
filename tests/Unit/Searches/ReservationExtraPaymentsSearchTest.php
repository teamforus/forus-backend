<?php

namespace Tests\Unit\Searches;

use App\Models\Organization;
use App\Models\ReservationExtraPayment;
use App\Scopes\Builders\ReservationExtraPaymentQuery;
use App\Searches\ReservationExtraPaymentsSearch;
use App\Traits\DoesTesting;
use Exception;
use Illuminate\Support\Carbon;
use Tests\Traits\MakesProductReservations;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestOrganizations;
use Tests\Traits\MakesTestVouchers;

class ReservationExtraPaymentsSearchTest extends SearchTestCase
{
    use DoesTesting;
    use MakesTestFunds;
    use MakesTestVouchers;
    use MakesTestOrganizations;
    use MakesProductReservations;

    /**
     * @return void
     */
    public function testQueryBuilds(): void
    {
        $search = new ReservationExtraPaymentsSearch([], ReservationExtraPayment::query());

        $this->assertQueryBuilds($search->query());
    }

    /**
     * @throws Exception
     * @return void
     */
    public function testFiltersByQuery(): void
    {
        $fundNamePart1 = 'match';
        $fundNamePart2 = 'other';

        $productNamePart1 = 'first';
        $productNamePart2 = 'last';

        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund1 = $this->makeTestFund($organization, ['name' => "$fundNamePart1 fund"]);
        $fund2 = $this->makeTestFund($organization, ['name' => "$fundNamePart2 fund"]);

        $voucher1 = $this->makeTestVoucher($fund1, identity: $this->makeIdentity());
        $voucher2 = $this->makeTestVoucher($fund2, identity: $this->makeIdentity());

        $product1 = $this->findProductForReservation($voucher1);
        $product1->update(['name' => "$productNamePart1 product name"]);

        $product2 = $this->findProductForReservation($voucher2);
        $product2->update(['name' => "$productNamePart2 product name"]);

        $reservation1 = $this->makeReservation($voucher1, $product1);
        $reservation2 = $this->makeReservation($voucher2, $product2);

        $extra1 = $this->makeTestExtraPayment($reservation1);
        $extra2 = $this->makeTestExtraPayment($reservation2);

        // assert by fund name
        $this->assertSearchIds(['q' => $fundNamePart1], [$extra1->id], $organization);
        $this->assertSearchIds(['q' => $fundNamePart2], [$extra2->id], $organization);

        // assert by product name
        $this->assertSearchIds(['q' => $productNamePart1], [$extra1->id], $organization);
        $this->assertSearchIds(['q' => $productNamePart2], [$extra2->id], $organization);
    }

    /**
     * @throws Exception
     * @return void
     */
    public function testFiltersByFundId(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund1 = $this->makeTestFund($organization);
        $fund2 = $this->makeTestFund($organization);

        $voucher1 = $this->makeTestVoucher($fund1, identity: $this->makeIdentity());
        $voucher2 = $this->makeTestVoucher($fund2, identity: $this->makeIdentity());

        $reservation1 = $this->makeReservation($voucher1, $this->findProductForReservation($voucher1));
        $reservation2 = $this->makeReservation($voucher2, $this->findProductForReservation($voucher2));

        $extra1 = $this->makeTestExtraPayment($reservation1);
        $extra2 = $this->makeTestExtraPayment($reservation2);

        // assert by fund id
        $this->assertSearchIds(['fund_id' => $fund1->id], [$extra1->id], $organization);
        $this->assertSearchIds(['fund_id' => $fund2->id], [$extra2->id], $organization);
    }

    /**
     * @throws Exception
     * @return void
     */
    public function testOrdersByPaidAt(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization);

        $voucher1 = $this->makeTestVoucher($fund, identity: $this->makeIdentity());
        $voucher2 = $this->makeTestVoucher($fund, identity: $this->makeIdentity());

        $reservation1 = $this->makeReservation($voucher1, $this->findProductForReservation($voucher1));
        $reservation2 = $this->makeReservation($voucher2, $this->findProductForReservation($voucher2));

        $olderExtra = $this->makeTestExtraPayment($reservation1);
        $olderExtra->update(['paid_at' => Carbon::now()->subDays(7)]);

        $newerExtra = $this->makeTestExtraPayment($reservation2);
        $newerExtra->update(['paid_at' => Carbon::now()]);

        $this->assertSearchOrder([
            'order_by' => 'paid_at',
            'order_dir' => 'asc',
        ], [$olderExtra->id, $newerExtra->id], $organization);

        $this->assertSearchOrder([
            'order_by' => 'paid_at',
            'order_dir' => 'desc',
        ], [$newerExtra->id, $olderExtra->id], $organization);
    }

    /**
     * @throws Exception
     * @return void
     */
    public function testOrdersById(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization);

        $voucher1 = $this->makeTestVoucher($fund, identity: $this->makeIdentity());
        $voucher2 = $this->makeTestVoucher($fund, identity: $this->makeIdentity());

        $reservation1 = $this->makeReservation($voucher1, $this->findProductForReservation($voucher1));
        $reservation2 = $this->makeReservation($voucher2, $this->findProductForReservation($voucher2));

        $olderExtra = $this->makeTestExtraPayment($reservation1);
        $newerExtra = $this->makeTestExtraPayment($reservation2);

        $this->assertSearchOrder([
            'order_by' => 'id',
            'order_dir' => 'asc',
        ], [$olderExtra->id, $newerExtra->id], $organization);

        $this->assertSearchOrder([
            'order_by' => 'id',
            'order_dir' => 'desc',
        ], [$newerExtra->id, $olderExtra->id], $organization);
    }

    /**
     * @throws Exception
     * @return void
     */
    public function testOrdersByPrice(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization);

        $voucher1 = $this->makeTestVoucher($fund, identity: $this->makeIdentity());
        $voucher2 = $this->makeTestVoucher($fund, identity: $this->makeIdentity());

        $product1 = $this->createProductForReservation($organization, [$fund], 5);
        $product2 = $this->createProductForReservation($organization, [$fund]);

        $reservation1 = $this->makeReservation($voucher1, $product1);
        $reservation2 = $this->makeReservation($voucher2, $product2);

        $olderExtra = $this->makeTestExtraPayment($reservation1);
        $newerExtra = $this->makeTestExtraPayment($reservation2);

        $this->assertSearchOrder([
            'order_by' => 'price',
            'order_dir' => 'asc',
        ], [$olderExtra->id, $newerExtra->id], $organization);

        $this->assertSearchOrder([
            'order_by' => 'price',
            'order_dir' => 'desc',
        ], [$newerExtra->id, $olderExtra->id], $organization);
    }

    /**
     * @throws Exception
     * @return void
     */
    public function testOrdersByAmount(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization);

        $voucher1 = $this->makeTestVoucher($fund, identity: $this->makeIdentity());
        $voucher2 = $this->makeTestVoucher($fund, identity: $this->makeIdentity());

        $product1 = $this->createProductForReservation($organization, [$fund]);
        $product2 = $this->createProductForReservation($organization, [$fund]);

        $reservation1 = $this->makeReservation($voucher1, $product1);
        $reservation2 = $this->makeReservation($voucher2, $product2);

        $olderExtra = $this->makeTestExtraPayment($reservation1, amount: 5);
        $newerExtra = $this->makeTestExtraPayment($reservation2, amount: 8);

        $this->assertSearchOrder([
            'order_by' => 'amount',
            'order_dir' => 'asc',
        ], [$olderExtra->id, $newerExtra->id], $organization);

        $this->assertSearchOrder([
            'order_by' => 'amount',
            'order_dir' => 'desc',
        ], [$newerExtra->id, $olderExtra->id], $organization);
    }

    /**
     * @throws Exception
     * @return void
     */
    public function testOrdersByFundName(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund1 = $this->makeTestFund($organization, ['name' => 'A fund']);
        $fund2 = $this->makeTestFund($organization, ['name' => 'B fund']);

        $voucher1 = $this->makeTestVoucher($fund1, identity: $this->makeIdentity());
        $voucher2 = $this->makeTestVoucher($fund2, identity: $this->makeIdentity());

        $product1 = $this->findProductForReservation($voucher1);
        $product2 = $this->findProductForReservation($voucher2);

        $reservation1 = $this->makeReservation($voucher1, $product1);
        $reservation2 = $this->makeReservation($voucher2, $product2);

        $olderExtra = $this->makeTestExtraPayment($reservation1);
        $newerExtra = $this->makeTestExtraPayment($reservation2);

        $this->assertSearchOrder([
            'order_by' => 'fund_name',
            'order_dir' => 'asc',
        ], [$olderExtra->id, $newerExtra->id], $organization);

        $this->assertSearchOrder([
            'order_by' => 'fund_name',
            'order_dir' => 'desc',
        ], [$newerExtra->id, $olderExtra->id], $organization);
    }

    /**
     * @throws Exception
     * @return void
     */
    public function testOrdersByProductName(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization);

        $voucher1 = $this->makeTestVoucher($fund, identity: $this->makeIdentity());
        $voucher2 = $this->makeTestVoucher($fund, identity: $this->makeIdentity());

        $product1 = $this->createProductForReservation($organization, [$fund]);
        $product1->update(['name' => 'A product name']);

        $product2 = $this->createProductForReservation($organization, [$fund]);
        $product2->update(['name' => 'B product name']);

        $reservation1 = $this->makeReservation($voucher1, $product1);
        $reservation2 = $this->makeReservation($voucher2, $product2);

        $olderExtra = $this->makeTestExtraPayment($reservation1);
        $newerExtra = $this->makeTestExtraPayment($reservation2);

        $this->assertSearchOrder([
            'order_by' => 'product_name',
            'order_dir' => 'asc',
        ], [$olderExtra->id, $newerExtra->id], $organization);

        $this->assertSearchOrder([
            'order_by' => 'product_name',
            'order_dir' => 'desc',
        ], [$newerExtra->id, $olderExtra->id], $organization);
    }

    /**
     * @throws Exception
     * @return void
     */
    public function testOrdersByProviderName(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization);

        $voucher1 = $this->makeTestVoucher($fund, identity: $this->makeIdentity());
        $voucher2 = $this->makeTestVoucher($fund, identity: $this->makeIdentity());

        $provider1 = $this->makeTestProviderOrganization($this->makeIdentity(), ['name' => 'A provider name']);
        $provider2 = $this->makeTestProviderOrganization($this->makeIdentity(), ['name' => 'B provider name']);

        $product1 = $this->createProductForReservation($provider1, [$fund]);
        $product2 = $this->createProductForReservation($provider2, [$fund]);

        $reservation1 = $this->makeReservation($voucher1, $product1);
        $reservation2 = $this->makeReservation($voucher2, $product2);

        $olderExtra = $this->makeTestExtraPayment($reservation1);
        $newerExtra = $this->makeTestExtraPayment($reservation2);

        $this->assertSearchOrder([
            'order_by' => 'provider_name',
            'order_dir' => 'asc',
        ], [$olderExtra->id, $newerExtra->id], $organization);

        $this->assertSearchOrder([
            'order_by' => 'provider_name',
            'order_dir' => 'desc',
        ], [$newerExtra->id, $olderExtra->id], $organization);
    }

    /**
     * @throws Exception
     * @return void
     */
    public function testOrdersByMethod(): void
    {
        $provider = $this->makeTestProviderOrganization($this->makeIdentity());
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization);

        $voucher1 = $this->makeTestVoucher($fund, identity: $this->makeIdentity());
        $voucher2 = $this->makeTestVoucher($fund, identity: $this->makeIdentity());

        $product1 = $this->createProductForReservation($provider, [$fund]);
        $product2 = $this->createProductForReservation($provider, [$fund]);

        $reservation1 = $this->makeReservation($voucher1, $product1);
        $reservation2 = $this->makeReservation($voucher2, $product2);

        $olderExtra = $this->makeTestExtraPayment($reservation1, fields: ['method' => 'A method']);
        $newerExtra = $this->makeTestExtraPayment($reservation2, fields: ['method' => 'B method']);

        $this->assertSearchOrder([
            'order_by' => 'method',
            'order_dir' => 'asc',
        ], [$olderExtra->id, $newerExtra->id], $organization);

        $this->assertSearchOrder([
            'order_by' => 'method',
            'order_dir' => 'desc',
        ], [$newerExtra->id, $olderExtra->id], $organization);
    }

    /**
     * @param array $filters
     * @param Organization $organization
     * @return ReservationExtraPaymentsSearch
     */
    private function makeSearch(array $filters, Organization $organization): ReservationExtraPaymentsSearch
    {
        return new ReservationExtraPaymentsSearch(
            $filters,
            ReservationExtraPaymentQuery::whereSponsorFilter(ReservationExtraPayment::query(), $organization->id)
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
