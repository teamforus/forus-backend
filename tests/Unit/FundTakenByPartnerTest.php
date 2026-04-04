<?php

namespace Tests\Unit;

use App\Traits\DoesTesting;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\CreatesApplication;
use Tests\TestCase;
use Tests\Traits\MakesTestFundRequests;
use Tests\Traits\MakesTestFunds;
use Throwable;

class FundTakenByPartnerTest extends TestCase
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
    public function testFundTakenByPartnerPendingFundRequestMatchesByPendingRequestRecord(): void
    {
        $fund = $this->makeTestFund($this->makeTestOrganization($this->makeIdentity()));
        $partnerWithPendingRequest = $this->makeIdentity($this->makeUniqueEmail());
        $requester = $this->makeIdentity($this->makeUniqueEmail());
        $this->makeFundRequestWithRecord($partnerWithPendingRequest, $fund, 'partner_bsn', '123456789');

        $this->assertFalse(
            $fund->isTakenByPartnerPendingFundRequest($requester),
            'Identity dont have partner with pending fund request',
        );

        $requester->setBsnRecord(123456789);

        $this->assertTrue(
            $fund->isTakenByPartnerPendingFundRequest($requester),
            'Identity have partner with pending fund request',
        );
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testFundTakenByPartnerPendingFundRequestMatchesByValidatedPartnerRecord(): void
    {
        $fund = $this->makeTestFund($this->makeTestOrganization($this->makeIdentity()));
        $requester = $this->makeIdentity($this->makeUniqueEmail(), '123456789');
        $partnerWithValidatedRecord = $this->makeIdentity($this->makeUniqueEmail());

        $this->makeValidatedIdentityRecordForFund($partnerWithValidatedRecord, $fund, 'partner_bsn', '123456789');
        $this->setCriteriaAndMakeFundRequest($partnerWithValidatedRecord, $fund, ['children_nth' => 3]);

        $this->assertTrue(
            $fund->isTakenByPartnerPendingFundRequest($requester),
            'Identity have partner with pending fund request',
        );
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testFundTakenByPartnerPendingFundRequestChecksAllMatchingPartners(): void
    {
        $fund = $this->makeTestFund($this->makeTestOrganization($this->makeIdentity()));
        $requester = $this->makeIdentity($this->makeUniqueEmail(), '123456789');
        $partnerWithVoucher = $this->makeIdentity($this->makeUniqueEmail());
        $partnerWithPendingRequest = $this->makeIdentity($this->makeUniqueEmail());

        $this->makeValidatedIdentityRecordForFund($partnerWithVoucher, $fund, 'partner_bsn', '123456789');
        $this->makeFundRequestWithRecord($partnerWithPendingRequest, $fund, 'partner_bsn', '123456789');

        $this->assertTrue(
            $fund->isTakenByPartnerPendingFundRequest($requester),
            'Identity have partner with pending fund request among multiple matches',
        );
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testFundTakenByPartnerApprovedRequestChecksAllMatchingPartners(): void
    {
        $fund = $this->makeTestFund($this->makeTestOrganization($this->makeIdentity()));
        $requester = $this->makeIdentity($this->makeUniqueEmail(), '123456789');
        $staleMatch = $this->makeIdentity($this->makeUniqueEmail());
        $partnerWithApprovedRequest = $this->makeIdentity($this->makeUniqueEmail());

        $this->makeValidatedIdentityRecordForFund($staleMatch, $fund, 'partner_bsn', '123456789');
        $this->makeValidatedIdentityRecordForFund($partnerWithApprovedRequest, $fund, 'partner_bsn', '123456789');

        $fundRequest = $this->setCriteriaAndMakeFundRequest($partnerWithApprovedRequest, $fund, ['children_nth' => 3]);
        $employee = $fund->organization->employees()->first();
        $fundRequest->assignEmployee($employee)->approve();
        $fund->makeVoucher($partnerWithApprovedRequest, ['amount' => 100]);

        $this->assertTrue(
            $fund->isTakenByPartnerPendingFundRequest($requester),
            'Identity have partner with approved request and active voucher among multiple matches',
        );
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testFundTakenByPartnerApprovedRequestDoesNotCombineDifferentMatchingPartners(): void
    {
        $fund = $this->makeTestFund($this->makeTestOrganization($this->makeIdentity()));
        $requester = $this->makeIdentity($this->makeUniqueEmail(), '123456789');
        $partnerWithVoucher = $this->makeIdentity($this->makeUniqueEmail());
        $partnerWithApprovedRequest = $this->makeIdentity($this->makeUniqueEmail());

        $this->makeValidatedIdentityRecordForFund($partnerWithVoucher, $fund, 'partner_bsn', '123456789');
        $this->makeValidatedIdentityRecordForFund($partnerWithApprovedRequest, $fund, 'partner_bsn', '123456789');

        $fundRequest = $this->setCriteriaAndMakeFundRequest($partnerWithApprovedRequest, $fund, ['children_nth' => 3]);
        $employee = $fund->organization->employees()->first();

        $fundRequest->assignEmployee($employee)->approve();
        $fund->makeVoucher($partnerWithVoucher, ['amount' => 100]);

        $this->assertFalse(
            $fund->isTakenByPartnerPendingFundRequest($requester),
            'Identity approved request and active voucher should match the same partner',
        );
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testFundTakenByPartnerPendingFundRequestIgnoresOtherFunds(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization);
        $otherFund = $this->makeTestFund($organization);
        $requester = $this->makeIdentity($this->makeUniqueEmail(), '123456789');
        $partner = $this->makeIdentity($this->makeUniqueEmail());

        $this->makeFundRequestWithRecord($partner, $otherFund, 'partner_bsn', '123456789');

        $this->assertFalse(
            $fund->isTakenByPartnerPendingFundRequest($requester),
            'Identity partner on other fund should not block current fund',
        );
    }
}
