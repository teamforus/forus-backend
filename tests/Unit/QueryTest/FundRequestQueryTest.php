<?php

namespace Tests\Unit\QueryTest;

use App\Models\Fund;
use App\Models\FundRequest;
use App\Models\Identity;
use App\Scopes\Builders\FundRequestQuery;
use App\Traits\DoesTesting;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\CreatesApplication;
use Tests\TestCase;
use Tests\Traits\MakesTestFundRequests;
use Tests\Traits\MakesTestFunds;
use Throwable;

class FundRequestQueryTest extends TestCase
{
    use DoesTesting;
    use MakesTestFunds;
    use CreatesApplication;
    use DatabaseTransactions;
    use MakesTestFundRequests;

    /**
     * @throws Throwable
     * @return void
     */
    public function testWhereIsPendingMatchesIntAndArrayIdentityIds(): void
    {
        $fund = $this->makeTestFund($this->makeTestOrganization($this->makeIdentity()));
        $pendingRequester = $this->makeIdentity($this->makeUniqueEmail());
        $otherRequester = $this->makeIdentity($this->makeUniqueEmail());

        $this->makePendingFundRequest($fund, $pendingRequester);

        $this->assertTrue($this->whereIsPendingExists($fund, $pendingRequester->id));
        $this->assertTrue($this->whereIsPendingExists($fund, [$otherRequester->id, $pendingRequester->id]));
        $this->assertFalse($this->whereIsPendingExists($fund, $otherRequester->id));
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testWhereApprovedAndVoucherIsActiveMatchesIntAndArrayIdentityIds(): void
    {
        $fund = $this->makeTestFund($this->makeTestOrganization($this->makeIdentity()));
        $approvedRequester = $this->makeIdentity($this->makeUniqueEmail());
        $otherRequester = $this->makeIdentity($this->makeUniqueEmail());

        $this->makeApprovedFundRequestWithVoucher($fund, $approvedRequester);

        $this->assertTrue($this->whereApprovedAndVoucherIsActiveExists($fund, $approvedRequester->id));
        $this->assertFalse($this->whereApprovedAndVoucherIsActiveExists($fund, $otherRequester->id));

        $this->assertTrue($this->whereApprovedAndVoucherIsActiveExists($fund, [
            $otherRequester->id, $approvedRequester->id,
        ]));
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testWhereApprovedAndVoucherIsActiveDoesNotCombineDifferentArrayIdentityMatches(): void
    {
        $fund = $this->makeTestFund($this->makeTestOrganization($this->makeIdentity()));
        $requesterWithApprovedRequest = $this->makeIdentity($this->makeUniqueEmail());
        $requesterWithVoucher = $this->makeIdentity($this->makeUniqueEmail());

        $this->makeApprovedFundRequest($fund, $requesterWithApprovedRequest);
        $fund->makeVoucher($requesterWithVoucher, ['amount' => 100]);

        $this->assertFalse($this->whereApprovedAndVoucherIsActiveExists($fund, [
            $requesterWithApprovedRequest->id, $requesterWithVoucher->id,
        ]));
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testWherePendingOrApprovedAndVoucherIsActiveMatchesPendingIntAndArrayIdentityIds(): void
    {
        $fund = $this->makeTestFund($this->makeTestOrganization($this->makeIdentity()));
        $pendingRequester = $this->makeIdentity($this->makeUniqueEmail());
        $otherRequester = $this->makeIdentity($this->makeUniqueEmail());

        $this->makePendingFundRequest($fund, $pendingRequester);

        $this->assertTrue($this->wherePendingOrApprovedAndVoucherIsActiveExists($fund, $pendingRequester->id));
        $this->assertFalse($this->wherePendingOrApprovedAndVoucherIsActiveExists($fund, $otherRequester->id));

        $this->assertTrue($this->wherePendingOrApprovedAndVoucherIsActiveExists($fund, [
            $otherRequester->id, $pendingRequester->id,
        ]));
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testWherePendingOrApprovedAndVoucherIsActiveMatchesApprovedIntAndArrayIdentityIds(): void
    {
        $fund = $this->makeTestFund($this->makeTestOrganization($this->makeIdentity()));
        $approvedRequester = $this->makeIdentity($this->makeUniqueEmail());
        $otherRequester = $this->makeIdentity($this->makeUniqueEmail());

        $this->makeApprovedFundRequestWithVoucher($fund, $approvedRequester);

        $this->assertTrue($this->wherePendingOrApprovedAndVoucherIsActiveExists($fund, $approvedRequester->id));
        $this->assertFalse($this->wherePendingOrApprovedAndVoucherIsActiveExists($fund, $otherRequester->id));

        $this->assertTrue($this->wherePendingOrApprovedAndVoucherIsActiveExists($fund, [
            $otherRequester->id, $approvedRequester->id,
        ]));
    }

    /**
     * @param Fund $fund
     * @param array|int $identityIds
     * @return bool
     */
    protected function whereIsPendingExists(Fund $fund, array|int $identityIds): bool
    {
        return FundRequestQuery::whereIsPending($fund->fund_requests(), $identityIds)->exists();
    }

    /**
     * @param Fund $fund
     * @param array|int $identityIds
     * @return bool
     */
    protected function whereApprovedAndVoucherIsActiveExists(Fund $fund, array|int $identityIds): bool
    {
        return FundRequestQuery::whereApprovedAndVoucherIsActive($fund->fund_requests(), $identityIds)->exists();
    }

    /**
     * @param Fund $fund
     * @param array|int $identityIds
     * @return bool
     */
    protected function wherePendingOrApprovedAndVoucherIsActiveExists(Fund $fund, array|int $identityIds): bool
    {
        return FundRequestQuery::wherePendingOrApprovedAndVoucherIsActive($fund->fund_requests(), $identityIds)->exists();
    }

    /**
     * @param Fund $fund
     * @param Identity $identity
     * @return FundRequest
     */
    protected function makePendingFundRequest(Fund $fund, Identity $identity): FundRequest
    {
        return $this->setCriteriaAndMakeFundRequest($identity, $fund, ['children_nth' => 3]);
    }

    /**
     * @param Fund $fund
     * @param Identity $identity
     * @return FundRequest
     */
    protected function makeApprovedFundRequest(Fund $fund, Identity $identity): FundRequest
    {
        $fundRequest = $this->makePendingFundRequest($fund, $identity);
        $employee = $fund->organization->employees()->first();

        return $fundRequest->assignEmployee($employee)->approve();
    }

    /**
     * @param Fund $fund
     * @param Identity $identity
     * @return FundRequest
     */
    protected function makeApprovedFundRequestWithVoucher(Fund $fund, Identity $identity): FundRequest
    {
        $fundRequest = $this->makeApprovedFundRequest($fund, $identity);
        $fund->makeVoucher($identity, ['amount' => 100]);

        return $fundRequest;
    }
}
