<?php

namespace Tests\Unit\Searches;

use App\Models\Data\BankAccount;
use App\Models\Voucher;
use App\Searches\VouchersSearch;
use App\Traits\DoesTesting;
use Illuminate\Support\Carbon;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestOrganizations;
use Tests\Traits\MakesTestVouchers;
use Throwable;

class VouchersSearchTest extends SearchTestCase
{
    use DoesTesting;
    use MakesTestFunds;
    use MakesTestVouchers;
    use MakesTestOrganizations;

    /**
     * @return void
     */
    public function testQueryBuilds(): void
    {
        $search = new VouchersSearch([], Voucher::query());

        $this->assertQueryBuilds($search->query());
    }

    /**
     * @return void
     */
    public function testFiltersByImplementationIdOrImplementationKey(): void
    {
        $organization1 = $this->makeTestOrganization($this->makeIdentity());
        $organization2 = $this->makeTestOrganization($this->makeIdentity());
        $fund1 = $this->makeTestFund($organization1);
        $fund2 = $this->makeTestFund($organization2);

        $voucher1 = $this->makeTestVoucher($fund1, identity: $this->makeIdentity());
        $voucher2 = $this->makeTestVoucher($fund2, identity: $this->makeIdentity());

        $this->assertSearchIds(['implementation_id' => $fund1->getImplementation()->id], [$voucher1->id]);
        $this->assertSearchIds(['implementation_id' => $fund2->getImplementation()->id], [$voucher2->id]);

        $this->assertSearchIds(['implementation_key' => $fund1->getImplementation()->key], [$voucher1->id]);
        $this->assertSearchIds(['implementation_key' => $fund2->getImplementation()->key], [$voucher2->id]);
    }

    /**
     * @return void
     */
    public function testFiltersByVoucherType(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization);

        $voucher = $this->makeTestVoucher($fund, identity: $this->makeIdentity());

        $fund->makePayout(
            identity: $this->makeIdentity(),
            amount: 100,
            employee: $organization->employees()->first(),
            bankAccount: new BankAccount($this->faker()->iban(), $this->faker()->name()),
        );

        $this->assertSearchIds(['implementation_id' => $fund->getImplementation()->id], [$voucher->id]);
    }

    /**
     * @return void
     */
    public function testFiltersByType(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization);

        $budgetVoucher = $this->makeTestVoucher($fund, identity: $this->makeIdentity());

        $product = $this->makeTestProviderWithProducts(1)[0];
        $this->addProductToFund($fund, $product, false);
        $productVoucher = $budgetVoucher->buyProductVoucher($product);

        $this->assertSearchIds([
            'implementation_id' => $fund->getImplementation()->id,
            'type' => Voucher::TYPE_BUDGET,
        ], [$budgetVoucher->id]);

        $this->assertSearchIds([
            'implementation_id' => $fund->getImplementation()->id,
            'type' => Voucher::TYPE_PRODUCT,
        ], [$productVoucher->id]);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testFiltersByState(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization);

        $activeVoucher = $this->makeTestVoucher($fund, identity: $this->makeIdentity());

        $pendingVoucher = $this->makeTestVoucher($fund, identity: $this->makeIdentity());
        $pendingVoucher->setPending();

        $deactivatedVoucher = $this->makeTestVoucher($fund, identity: $this->makeIdentity())->deactivate();

        $this->assertSearchIds([
            'implementation_id' => $fund->getImplementation()->id,
            'state' => Voucher::STATE_ACTIVE,
        ], [$activeVoucher->id]);

        $this->assertSearchIds([
            'implementation_id' => $fund->getImplementation()->id,
            'state' => Voucher::STATE_PENDING,
        ], [$pendingVoucher->id]);

        $this->assertSearchIds([
            'implementation_id' => $fund->getImplementation()->id,
            'state' => Voucher::STATE_DEACTIVATED,
        ], [$deactivatedVoucher->id]);
    }

    /**
     * @return void
     */
    public function testFiltersByAllowReimbursements(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());

        $fund1 = $this->makeTestFund($organization);
        $fund1->fund_config->forceFill(['allow_reimbursements' => true])->save();

        $fund2 = $this->makeTestFund($organization);
        $fund2->fund_config->forceFill(['allow_reimbursements' => false])->save();

        $voucher1 = $this->makeTestVoucher($fund1, identity: $this->makeIdentity());
        $voucher2 = $this->makeTestVoucher($fund2, identity: $this->makeIdentity());

        $this->assertSearchIds([
            'implementation_id' => $fund2->getImplementation()->id,
        ], [$voucher1->id, $voucher2->id]);

        $this->assertSearchIds([
            'implementation_id' => $fund1->getImplementation()->id,
            'allow_reimbursements' => true,
        ], [$voucher1->id]);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testFiltersByArchived(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization);

        $activeVoucher = $this->makeTestVoucher($fund, identity: $this->makeIdentity());
        $deactivatedVoucher = $this->makeTestVoucher($fund, identity: $this->makeIdentity())->deactivate();
        $expiredVoucher = $this->makeTestVoucher($fund, $this->makeIdentity(), ['expire_at' => Carbon::now()->subDay()]);

        $this->assertSearchIds([
            'implementation_id' => $fund->getImplementation()->id,
            'archived' => false,
        ], [$activeVoucher->id]);

        $this->assertSearchIds([
            'implementation_id' => $fund->getImplementation()->id,
            'archived' => true,
        ], [$expiredVoucher->id, $deactivatedVoucher->id]);
    }

    /**
     * @return void
     */
    public function testOrdersByCreatedAt(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization);

        $voucherA = $this->makeTestVoucher($fund, identity: $this->makeIdentity());

        Carbon::setTestNow(Carbon::now()->addDays(5));
        $voucherB = $this->makeTestVoucher($fund, identity: $this->makeIdentity());

        $this->assertSearchOrder([
            'implementation_id' => $fund->getImplementation()->id,
            'order_by' => 'created_at',
            'order_dir' => 'asc',
        ], [$voucherA->id, $voucherB->id]);

        $this->assertSearchOrder([
            'implementation_id' => $fund->getImplementation()->id,
            'order_by' => 'created_at',
            'order_dir' => 'desc',
        ], [$voucherB->id, $voucherA->id]);
    }

    /**
     * @return void
     */
    public function testOrdersByVoucherType(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization);

        $budgetVoucher = $this->makeTestVoucher($fund, identity: $this->makeIdentity());

        $product = $this->makeTestProviderWithProducts(1)[0];
        $this->addProductToFund($fund, $product, false);
        $productVoucher = $budgetVoucher->buyProductVoucher($product);

        $this->assertSearchOrder([
            'implementation_id' => $fund->getImplementation()->id,
            'order_by' => 'voucher_type',
            'order_dir' => 'asc',
        ], [$productVoucher->id, $budgetVoucher->id]);

        $this->assertSearchOrder([
            'implementation_id' => $fund->getImplementation()->id,
            'order_by' => 'voucher_type',
            'order_dir' => 'desc',
        ], [$budgetVoucher->id, $productVoucher->id]);
    }

    /**
     * @param array $filters
     * @return VouchersSearch
     */
    private function makeSearch(array $filters): VouchersSearch
    {
        return new VouchersSearch($filters, Voucher::query());
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

    /**
     * @param array $filters
     * @param array $expectedIds
     * @return void
     */
    private function assertSearchOrder(array $filters, array $expectedIds): void
    {
        $search = $this->makeSearch($filters);
        $actual = $search->query()->pluck('id')->toArray();

        $this->assertSame($expectedIds, $actual);
    }
}
