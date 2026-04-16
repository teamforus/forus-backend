<?php

namespace Tests\Unit\Searches\Sponsor\PayoutBankAccounts;

use App\Models\Fund;
use App\Models\FundRequest;
use App\Models\FundRequestRecord;
use App\Models\Identity;
use App\Models\Organization;
use App\Models\RecordType;
use App\Searches\Sponsor\PayoutBankAccounts\FundRequestPayoutBankAccountSearch;
use App\Traits\DoesTesting;
use Tests\Traits\MakesTestFundRequests;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestOrganizations;
use Tests\Traits\MakesTestVouchers;
use Tests\Unit\Searches\SearchTestCase;

class FundRequestPayoutBankAccountSearchTest extends SearchTestCase
{
    use DoesTesting;
    use MakesTestFunds;
    use MakesTestVouchers;
    use MakesTestFundRequests;
    use MakesTestOrganizations;

    /**
     * @return void
     */
    public function testQueryBuilds(): void
    {
        $identity = $this->makeIdentity();
        $organization = $this->makeTestOrganization($identity, ['bsn_enabled' => true]);

        $search = new FundRequestPayoutBankAccountSearch($organization, []);

        $this->assertQueryBuilds($search->query());
    }

    /**
     * @return void
     */
    public function testFiltersByIdentityId(): void
    {
        [$organization, $recordTypeIban, $recordTypeIbanName] = $this->prepareOrganization();

        $fund = $this->makeTestFund($organization, fundConfigsData: [
            'iban_record_key' => 'test_iban',
            'iban_name_record_key' => 'test_iban_name',
        ]);

        // make identity and pending fund request
        $identity = $this->makeIdentity($this->makeUniqueEmail());
        $fundRequest = $this->makeFundRequestForIdentity($fund, $identity);

        // assert that no fund requests can be filtered as fund request is pending and has no iban records
        $this->assertSearchIds([], [], $organization);

        // add records to fund request
        $fundRequest->records()->create([
            'record_type_key' => $recordTypeIban->key,
            'value' => 'iban',
            'source' => FundRequestRecord::SOURCE_FORM,
        ]);

        $fundRequest->records()->create([
            'record_type_key' => $recordTypeIbanName->key,
            'value' => 'iban name',
            'source' => FundRequestRecord::SOURCE_FORM,
        ]);

        // assert that no fund requests can be filtered as fund request is pending
        $this->assertSearchIds([], [], $organization);

        // approve fund request
        $fundRequest->assignEmployee($organization->employees()->first())->approve();
        $this->assertNotEmpty($fundRequest->vouchers()->get());

        // assert without filters that request is visible
        $this->assertSearchIds([], [$fundRequest->id], $organization);

        // assert filter by identity_id
        $this->assertSearchIds([
            'identity_id' => $identity->id,
        ], [$fundRequest->id], $organization);
    }

    /**
     * @return void
     */
    public function testFiltersByQuery(): void
    {
        $bsnPart1 = '12345';
        $bsnPart2 = '45678';

        $emailPart1 = 'f_anywhere_not_used_email';
        $emailPart2 = 's_not_used_anywhere_email';

        $fundNamePart1 = 'first';
        $fundNamePart2 = 'last';

        $voucherNumberPart1 = '44444';
        $voucherNumberPart2 = '66666';

        $identity1 = $this->makeIdentity($this->makeUniqueEmail($emailPart1), "{$bsnPart1}9999");
        $identity2 = $this->makeIdentity($this->makeUniqueEmail($emailPart2), "{$bsnPart2}8888");

        [$organization, $recordTypeIban, $recordTypeIbanName] = $this->prepareOrganization();

        $fund1 = $this->makeTestFund($organization, ['name' => "$fundNamePart1 fund name"], [
            'iban_record_key' => 'test_iban',
            'iban_name_record_key' => 'test_iban_name',
        ]);

        $fund2 = $this->makeTestFund($organization, ['name' => "$fundNamePart2 fund name"], [
            'iban_record_key' => 'test_iban',
            'iban_name_record_key' => 'test_iban_name',
        ]);

        $fundRequest1 = $this->prepareFundRequest($fund1, $identity1, $recordTypeIban, $recordTypeIbanName);
        $voucher1 = $fundRequest1->vouchers()->first();
        $voucher1->update(['number' => "{$voucherNumberPart1}9999"]);

        $fundRequest2 = $this->prepareFundRequest($fund2, $identity2, $recordTypeIban, $recordTypeIbanName);
        $voucher2 = $fundRequest2->vouchers()->first();
        $voucher2->update(['number' => "{$voucherNumberPart2}9999"]);

        // assert filter by q for first fund request
        $this->assertSearchIds([
            'q' => $bsnPart1,
        ], [$fundRequest1->id], $organization);

        $this->assertSearchIds([
            'q' => $emailPart1,
        ], [$fundRequest1->id], $organization);

        $this->assertSearchIds([
            'q' => $fundNamePart1,
        ], [$fundRequest1->id], $organization);

        $this->assertSearchIds([
            'q' => $voucherNumberPart1,
        ], [$fundRequest1->id], $organization);

        $this->assertSearchIds([
            'q' => $voucher1->id,
        ], [$fundRequest1->id], $organization);

        // assert filter by q for second fund request
        $this->assertSearchIds([
            'q' => $bsnPart2,
        ], [$fundRequest2->id], $organization);

        $this->assertSearchIds([
            'q' => $emailPart2,
        ], [$fundRequest2->id], $organization);

        $this->assertSearchIds([
            'q' => $fundNamePart2,
        ], [$fundRequest2->id], $organization);

        $this->assertSearchIds([
            'q' => $voucherNumberPart2,
        ], [$fundRequest2->id], $organization);

        $this->assertSearchIds([
            'q' => $voucher2->id,
        ], [$fundRequest2->id], $organization);
    }

    /**
     * @return array
     */
    protected function prepareOrganization(): array
    {
        $organization = $this->makeTestOrganization($this->makeIdentity(), [
            'bsn_enabled' => true,
            'fund_request_resolve_policy' => Organization::FUND_REQUEST_POLICY_AUTO_REQUESTED,
        ]);

        $recordTypeIban = $this->makeRecordType(
            $organization,
            RecordType::TYPE_STRING,
            'test_iban',
        );

        $recordTypeIbanName = $this->makeRecordType(
            $organization,
            RecordType::TYPE_STRING,
            'test_iban_name',
        );

        return [$organization, $recordTypeIban, $recordTypeIbanName];
    }

    /**
     * @param Fund $fund
     * @param Identity $identity
     * @param RecordType $recordTypeIban
     * @param RecordType $recordTypeIbanName
     * @return FundRequest
     */
    protected function prepareFundRequest(
        Fund $fund,
        Identity $identity,
        RecordType $recordTypeIban,
        RecordType $recordTypeIbanName,
    ): FundRequest {
        $fundRequest = $this->makeFundRequestForIdentity($fund, $identity);
        $employee = $fund->organization->employees()->first();

        // add records to fund request
        $fundRequest->records()->create([
            'record_type_key' => $recordTypeIban->key,
            'value' => 'iban',
            'source' => FundRequestRecord::SOURCE_FORM,
        ]);

        $fundRequest->records()->create([
            'record_type_key' => $recordTypeIbanName->key,
            'value' => 'iban name',
            'source' => FundRequestRecord::SOURCE_FORM,
        ]);

        // approve fund request
        $fundRequest->assignEmployee($employee)->approve();
        $this->assertNotEmpty($fundRequest->vouchers()->get());

        return $fundRequest;
    }

    /**
     * @param array $filters
     * @param Organization $organization
     * @return FundRequestPayoutBankAccountSearch
     */
    private function makeSearch(array $filters, Organization $organization): FundRequestPayoutBankAccountSearch
    {
        return new FundRequestPayoutBankAccountSearch($organization, $filters);
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
}
