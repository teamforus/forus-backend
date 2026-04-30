<?php

namespace Tests\Unit\Searches;

use App\Models\Reimbursement;
use App\Searches\ReimbursementsSearch;
use App\Traits\DoesTesting;
use Illuminate\Support\Carbon;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestOrganizations;
use Tests\Traits\MakesTestReimbursements;
use Tests\Traits\MakesTestVouchers;
use Throwable;

class ReimbursementsSearchTest extends SearchTestCase
{
    use DoesTesting;
    use MakesTestFunds;
    use MakesTestVouchers;
    use MakesTestOrganizations;
    use MakesTestReimbursements;

    /**
     * @return void
     */
    public function testQueryBuilds(): void
    {
        $search = new ReimbursementsSearch([], Reimbursement::query());

        $this->assertQueryBuilds($search->query());
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testFiltersByQuery(): void
    {
        $bsnPart1 = '12345';
        $bsnPart2 = '45678';

        $emailPart1 = 'f_anywhere_not_used_email';
        $emailPart2 = 's_not_used_anywhere_email';

        $organization = $this->makeTestOrganization($this->makeIdentity());

        $identity1 = $this->makeIdentity($this->makeUniqueEmail($emailPart1), "{$bsnPart1}9999");
        $identity2 = $this->makeIdentity($this->makeUniqueEmail($emailPart2), "{$bsnPart2}8888");

        $voucher1 = $this
            ->makeTestFund($organization, fundConfigsData: ['allow_reimbursements' => true])
            ->makeVoucher($identity1);

        $voucher2 = $this
            ->makeTestFund($organization, fundConfigsData: ['allow_reimbursements' => true])
            ->makeVoucher($identity2);

        $reimbursement1 = $this->makeReimbursement($voucher1, true);
        $reimbursement2 = $this->makeReimbursement($voucher2, true);

        // assert by bsn
        $this->assertSearchIds(['q' => $bsnPart1], [$reimbursement1->id]);
        $this->assertSearchIds(['q' => $bsnPart2], [$reimbursement2->id]);

        // assert by email
        $this->assertSearchIds(['q' => $emailPart1], [$reimbursement1->id]);
        $this->assertSearchIds(['q' => $emailPart2], [$reimbursement2->id]);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testFiltersByFundId(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund1 = $this->makeTestFund($organization, fundConfigsData: ['allow_reimbursements' => true]);
        $fund2 = $this->makeTestFund($organization, fundConfigsData: ['allow_reimbursements' => true]);

        $voucher1 = $this->makeTestVoucher($fund1, identity: $this->makeIdentity($this->makeUniqueEmail()));
        $voucher2 = $this->makeTestVoucher($fund2, identity: $this->makeIdentity($this->makeUniqueEmail()));

        $reimbursement1 = $this->makeReimbursement($voucher1, true);
        $reimbursement2 = $this->makeReimbursement($voucher2, true);

        $this->assertSearchIds(['fund_id' => $fund1->id], [$reimbursement1->id]);
        $this->assertSearchIds(['fund_id' => $fund2->id], [$reimbursement2->id]);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testFilterByCreatedAt(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization, fundConfigsData: ['allow_reimbursements' => true]);

        $voucher1 = $this->makeTestVoucher($fund, identity: $this->makeIdentity($this->makeUniqueEmail()));
        $voucher2 = $this->makeTestVoucher($fund, identity: $this->makeIdentity($this->makeUniqueEmail()));

        $reimbursement1 = $this->makeReimbursement($voucher1, true);
        $reimbursement1->created_at = Carbon::now()->addDays(2);
        $reimbursement1->save();

        $reimbursement2 = $this->makeReimbursement($voucher2, true);
        $reimbursement2->created_at = Carbon::now()->addDays(10);
        $reimbursement2->save();

        $this->assertSearchIds([
            'from' => Carbon::now()->addDays()->format('Y-m-d'),
            'to' => Carbon::now()->addDays(5)->format('Y-m-d'),
        ], [$reimbursement1->id]);

        $this->assertSearchIds([
            'from' => Carbon::now()->addDays(5)->format('Y-m-d'),
            'to' => Carbon::now()->addDays(12)->format('Y-m-d'),
        ], [$reimbursement2->id]);

        $this->assertSearchIds([
            'from' => Carbon::now()->addDays(5)->format('Y-m-d'),
        ], [$reimbursement2->id]);

        $this->assertSearchIds([
            'fund_id' => $fund->id,
            'to' => Carbon::now()->addDays(5)->format('Y-m-d'),
        ], [$reimbursement1->id]);

        $this->assertSearchIds([
            'from' => Carbon::now()->addDays()->format('Y-m-d'),
            'to' => Carbon::now()->addDays(12)->format('Y-m-d'),
        ], [$reimbursement1->id, $reimbursement2->id]);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testFiltersByAmount(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization, fundConfigsData: ['allow_reimbursements' => true]);

        $voucher1 = $this->makeTestVoucher($fund, identity: $this->makeIdentity($this->makeUniqueEmail()));
        $voucher2 = $this->makeTestVoucher($fund, identity: $this->makeIdentity($this->makeUniqueEmail()));

        $reimbursement1 = $this->makeReimbursement($voucher1, true);
        $reimbursement1->update(['amount' => 5]);

        $reimbursement2 = $this->makeReimbursement($voucher2, true);
        $reimbursement2->update(['amount' => 10]);

        $this->assertSearchIds(['amount_min' => 4], [$reimbursement1->id, $reimbursement2->id]);
        $this->assertSearchIds(['amount_min' => 6], [$reimbursement2->id]);
        $this->assertSearchIds(['amount_max' => 12], [$reimbursement1->id, $reimbursement2->id]);
        $this->assertSearchIds(['amount_max' => 6], [$reimbursement1->id]);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testFiltersByIdentityAddress(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization, fundConfigsData: ['allow_reimbursements' => true]);

        $voucher1 = $this->makeTestVoucher($fund, identity: $this->makeIdentity($this->makeUniqueEmail()));
        $voucher2 = $this->makeTestVoucher($fund, identity: $this->makeIdentity($this->makeUniqueEmail()));

        $reimbursement1 = $this->makeReimbursement($voucher1, true);
        $reimbursement2 = $this->makeReimbursement($voucher2, true);

        $this->assertSearchIds(['identity_address' => $voucher1->identity->address], [$reimbursement1->id]);
        $this->assertSearchIds(['identity_address' => $voucher2->identity->address], [$reimbursement2->id]);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testFiltersByImplementationId(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $implementation1 = $this->makeTestImplementation($organization);
        $implementation2 = $this->makeTestImplementation($organization);

        $fund1 = $this->makeTestFund(
            $organization,
            fundConfigsData: ['allow_reimbursements' => true],
            implementation: $implementation1
        );

        $fund2 = $this->makeTestFund(
            $organization,
            fundConfigsData: ['allow_reimbursements' => true],
            implementation: $implementation2
        );

        $voucher1 = $this->makeTestVoucher($fund1, identity: $this->makeIdentity($this->makeUniqueEmail()));
        $voucher2 = $this->makeTestVoucher($fund2, identity: $this->makeIdentity($this->makeUniqueEmail()));

        $reimbursement1 = $this->makeReimbursement($voucher1, true);
        $reimbursement2 = $this->makeReimbursement($voucher2, true);

        $this->assertSearchIds(['implementation_id' => $implementation1->id], [$reimbursement1->id]);
        $this->assertSearchIds(['implementation_id' => $implementation2->id], [$reimbursement2->id]);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testFilterByBaseState(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $employee = $organization->employees()->first();

        $fund = $this->makeTestFund($organization, fundConfigsData: ['allow_reimbursements' => true]);

        $voucher1 = $this->makeTestVoucher($fund, identity: $this->makeIdentity($this->makeUniqueEmail()));
        $voucher2 = $this->makeTestVoucher($fund, identity: $this->makeIdentity($this->makeUniqueEmail()));

        // first reimbursement will be draft
        $reimbursement1 = $this->makeReimbursement($voucher1, false);

        // second reimbursement will be pending
        $reimbursement2 = $this->makeReimbursement($voucher2, true);

        // assert two pending reservations
        $this->assertSearchIds([
            'fund_id' => $fund->id,
            'state' => $reimbursement1::STATE_DRAFT,
        ], [$reimbursement1->id]);

        // assert two pending reservations
        $this->assertSearchIds([
            'fund_id' => $fund->id,
            'state' => $reimbursement1::STATE_PENDING,
        ], [$reimbursement2->id]);

        // approve first reimbursement and assert that second still can be filtered as pending
        // and first as approved
        $reimbursement1->assign($employee)->approve();

        $this->assertSearchIds([
            'fund_id' => $fund->id,
            'state' => $reimbursement1::STATE_PENDING,
        ], [$reimbursement2->id]);

        $this->assertSearchIds([
            'fund_id' => $fund->id,
            'state' => $reimbursement2::STATE_APPROVED,
        ], [$reimbursement1->id]);

        // decline second reimbursement and assert that first still can be filtered as approved
        // and second as declined
        $reimbursement2->assign($employee)->decline();

        $this->assertSearchIds([
            'fund_id' => $fund->id,
            'state' => $reimbursement1::STATE_APPROVED,
        ], [$reimbursement1->id]);

        $this->assertSearchIds([
            'fund_id' => $fund->id,
            'state' => $reimbursement2::STATE_DECLINED,
        ], [$reimbursement2->id]);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testFilterByDeactivated(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization, fundConfigsData: ['allow_reimbursements' => true]);

        $voucher1 = $this->makeTestVoucher($fund, identity: $this->makeIdentity($this->makeUniqueEmail()));
        $voucher2 = $this->makeTestVoucher($fund, identity: $this->makeIdentity($this->makeUniqueEmail()));

        $reimbursement1 = $this->makeReimbursement($voucher1, true);
        $reimbursement2 = $this->makeReimbursement($voucher2, true);

        $this->assertSearchIds([
            'fund_id' => $fund->id,
            'deactivated' => false,
        ], [$reimbursement1->id, $reimbursement2->id]);

        $this->assertSearchIds([
            'fund_id' => $fund->id,
            'deactivated' => true,
        ], []);

        $voucher1->deactivate();

        $this->assertSearchIds([
            'fund_id' => $fund->id,
            'deactivated' => false,
        ], [$reimbursement2->id]);

        $this->assertSearchIds([
            'fund_id' => $fund->id,
            'deactivated' => true,
        ], [$reimbursement1->id]);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testFilterByExpired(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization, fundConfigsData: ['allow_reimbursements' => true]);

        $voucher1 = $this->makeTestVoucher($fund, identity: $this->makeIdentity($this->makeUniqueEmail()));
        $voucher2 = $this->makeTestVoucher($fund, identity: $this->makeIdentity($this->makeUniqueEmail()));

        $reimbursement1 = $this->makeReimbursement($voucher1, true);
        $reimbursement2 = $this->makeReimbursement($voucher2, true);

        $this->assertSearchIds([
            'fund_id' => $fund->id,
            'expired' => false,
        ], [$reimbursement1->id, $reimbursement2->id]);

        $this->assertSearchIds([
            'fund_id' => $fund->id,
            'expired' => true,
        ], []);

        $voucher1->update(['expire_at' => Carbon::now()->subDay()]);

        $this->assertSearchIds([
            'fund_id' => $fund->id,
            'expired' => false,
        ], [$reimbursement2->id]);

        $this->assertSearchIds([
            'fund_id' => $fund->id,
            'expired' => true,
        ], [$reimbursement1->id]);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testFilterByArchived(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization, fundConfigsData: ['allow_reimbursements' => true]);

        $voucher1 = $this->makeTestVoucher($fund, identity: $this->makeIdentity($this->makeUniqueEmail()));
        $voucher2 = $this->makeTestVoucher($fund, identity: $this->makeIdentity($this->makeUniqueEmail()));

        $reimbursement1 = $this->makeReimbursement($voucher1, true);
        $reimbursement2 = $this->makeReimbursement($voucher2, true);

        $this->assertSearchIds([
            'fund_id' => $fund->id,
            'archived' => false,
        ], [$reimbursement1->id, $reimbursement2->id]);

        $this->assertSearchIds([
            'fund_id' => $fund->id,
            'archived' => true,
        ], []);

        // set first voucher as expired and assert it can be filtered by archived
        $voucher1->update(['expire_at' => Carbon::now()->subDay()]);

        $this->assertSearchIds([
            'fund_id' => $fund->id,
            'archived' => false,
        ], [$reimbursement2->id]);

        $this->assertSearchIds([
            'fund_id' => $fund->id,
            'archived' => true,
        ], [$reimbursement1->id]);

        // deactivate second voucher and assert that two vouchers
        // can be filtered as archived - deactivated and expired
        $voucher2->deactivate();

        $this->assertSearchIds([
            'fund_id' => $fund->id,
            'archived' => false,
        ], []);

        $this->assertSearchIds([
            'fund_id' => $fund->id,
            'archived' => true,
        ], [$reimbursement1->id, $reimbursement2->id]);
    }

    /**
     * @param array $filters
     * @return ReimbursementsSearch
     */
    private function makeSearch(array $filters): ReimbursementsSearch
    {
        return new ReimbursementsSearch($filters, Reimbursement::query());
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
