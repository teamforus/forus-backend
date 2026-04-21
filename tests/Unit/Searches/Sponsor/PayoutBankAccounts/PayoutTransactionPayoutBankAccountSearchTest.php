<?php

namespace Tests\Unit\Searches\Sponsor\PayoutBankAccounts;

use App\Models\Fund;
use App\Models\FundRequest;
use App\Models\FundRequestRecord;
use App\Models\Identity;
use App\Models\Organization;
use App\Models\RecordType;
use App\Models\VoucherTransaction;
use App\Searches\Sponsor\PayoutBankAccounts\PayoutTransactionPayoutBankAccountSearch;
use App\Traits\DoesTesting;
use Tests\Traits\MakesTestFundRequests;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestOrganizations;
use Tests\Traits\MakesTestVouchers;
use Tests\Unit\Searches\SearchTestCase;
use Throwable;

class PayoutTransactionPayoutBankAccountSearchTest extends SearchTestCase
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

        $search = new PayoutTransactionPayoutBankAccountSearch($organization, []);

        $this->assertQueryBuilds($search->query());
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testFiltersByIdentityId(): void
    {
        $identity1 = $this->makeIdentity($this->makeUniqueEmail());
        $identity2 = $this->makeIdentity($this->makeUniqueEmail());

        [$organization, $recordTypeIban, $recordTypeIbanName] = $this->prepareOrganization();

        $fund1 = $this->makeTestFund($organization, fundConfigsData: [
            'allow_voucher_payouts' => true,
            'iban_record_key' => 'test_iban',
            'iban_name_record_key' => 'test_iban_name',
        ]);

        $fund2 = $this->makeTestFund($organization, fundConfigsData: [
            'allow_voucher_payouts' => true,
            'iban_record_key' => 'test_iban',
            'iban_name_record_key' => 'test_iban_name',
        ]);

        // assert no transactions visible
        $this->assertSearchIds([], [], $organization);

        $fundRequest1 = $this->prepareFundRequest($fund1, $identity1, $recordTypeIban, $recordTypeIbanName);

        $transaction1 = $fundRequest1->vouchers()->first()->makeTransaction([
            'initiator' => VoucherTransaction::INITIATOR_SPONSOR,
            'target' => VoucherTransaction::TARGET_PAYOUT,
            'target_iban' => $this->makeIban(),
            'target_name' => $this->makeIbanName(),
            'amount' => '10.00',
        ]);

        $fundRequest2 = $this->prepareFundRequest($fund2, $identity2, $recordTypeIban, $recordTypeIbanName);

        $transaction2 = $fundRequest2->vouchers()->first()->makeTransaction([
            'initiator' => VoucherTransaction::INITIATOR_SPONSOR,
            'target' => VoucherTransaction::TARGET_PAYOUT,
            'target_iban' => $this->makeIban(),
            'target_name' => $this->makeIbanName(),
            'amount' => '10.00',
        ]);

        $this->assertSearchIds([], [$transaction1->id, $transaction2->id], $organization);

        // assert filter by identity id
        $this->assertSearchIds([
            'identity_id' => $identity1->id,
        ], [$transaction1->id], $organization);

        $this->assertSearchIds([
            'identity_id' => $identity2->id,
        ], [$transaction2->id], $organization);
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

        $namePart1 = 'first';
        $namePart2 = 'last';

        $ibanPart1 = '44444';
        $ibanPart2 = '66666';

        $identity1 = $this->makeIdentity($this->makeUniqueEmail($emailPart1), "{$bsnPart1}9999");
        $identity2 = $this->makeIdentity($this->makeUniqueEmail($emailPart2), "{$bsnPart2}8888");

        [$organization, $recordTypeIban, $recordTypeIbanName] = $this->prepareOrganization();

        $fund1 = $this->makeTestFund($organization, fundConfigsData: [
            'allow_voucher_payouts' => true,
            'iban_record_key' => 'test_iban',
            'iban_name_record_key' => 'test_iban_name',
        ]);

        $fund2 = $this->makeTestFund($organization, fundConfigsData: [
            'allow_voucher_payouts' => true,
            'iban_record_key' => 'test_iban',
            'iban_name_record_key' => 'test_iban_name',
        ]);

        $fundRequest1 = $this->prepareFundRequest($fund1, $identity1, $recordTypeIban, $recordTypeIbanName);

        $transaction1 = $fundRequest1->vouchers()->first()->makeTransaction([
            'initiator' => VoucherTransaction::INITIATOR_SPONSOR,
            'target' => VoucherTransaction::TARGET_PAYOUT,
            'target_iban' => "NL{$ibanPart1}55555",
            'target_name' => "$namePart1 name",
            'amount' => '10.00',
        ]);

        $fundRequest2 = $this->prepareFundRequest($fund2, $identity2, $recordTypeIban, $recordTypeIbanName);

        $transaction2 = $fundRequest2->vouchers()->first()->makeTransaction([
            'initiator' => VoucherTransaction::INITIATOR_SPONSOR,
            'target' => VoucherTransaction::TARGET_PAYOUT,
            'target_iban' => "NL{$ibanPart2}55555",
            'target_name' => "$namePart2 name",
            'amount' => '10.00',
        ]);

        // assert filter by q for first transaction
        $this->assertSearchIds([
            'q' => $bsnPart1,
        ], [$transaction1->id], $organization);

        $this->assertSearchIds([
            'q' => $emailPart1,
        ], [$transaction1->id], $organization);

        $this->assertSearchIds([
            'q' => $ibanPart1,
        ], [$transaction1->id], $organization);

        $this->assertSearchIds([
            'q' => $namePart1,
        ], [$transaction1->id], $organization);

        // assert filter by q for second transaction
        $this->assertSearchIds([
            'q' => $bsnPart2,
        ], [$transaction2->id], $organization);

        $this->assertSearchIds([
            'q' => $emailPart2,
        ], [$transaction2->id], $organization);

        $this->assertSearchIds([
            'q' => $ibanPart2,
        ], [$transaction2->id], $organization);

        $this->assertSearchIds([
            'q' => $namePart2,
        ], [$transaction2->id], $organization);
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
     * @return PayoutTransactionPayoutBankAccountSearch
     */
    private function makeSearch(array $filters, Organization $organization): PayoutTransactionPayoutBankAccountSearch
    {
        return new PayoutTransactionPayoutBankAccountSearch($organization, $filters);
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
