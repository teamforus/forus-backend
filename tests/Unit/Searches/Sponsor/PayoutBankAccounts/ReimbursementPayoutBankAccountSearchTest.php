<?php

namespace Tests\Unit\Searches\Sponsor\PayoutBankAccounts;

use App\Models\Organization;
use App\Searches\Sponsor\PayoutBankAccounts\ReimbursementPayoutBankAccountSearch;
use App\Traits\DoesTesting;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestOrganizations;
use Tests\Traits\MakesTestReimbursements;
use Tests\Traits\MakesTestVouchers;
use Tests\Unit\Searches\SearchTestCase;
use Throwable;

class ReimbursementPayoutBankAccountSearchTest extends SearchTestCase
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
        $identity = $this->makeIdentity();
        $organization = $this->makeTestOrganization($identity, ['bsn_enabled' => true]);

        $search = new ReimbursementPayoutBankAccountSearch($organization, []);

        $this->assertQueryBuilds($search->query());
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testFiltersByIdentityId(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity(), ['bsn_enabled' => true]);
        $employee = $organization->employees()->first();

        $identity1 = $this->makeIdentity($this->makeUniqueEmail());
        $identity2 = $this->makeIdentity($this->makeUniqueEmail());

        $voucher1 = $this
            ->makeTestFund($organization, fundConfigsData: ['allow_reimbursements' => true])
            ->makeVoucher($identity1);

        $voucher2 = $this
            ->makeTestFund($organization, fundConfigsData: ['allow_reimbursements' => true])
            ->makeVoucher($identity2);

        $reimbursement1 = $this->makeReimbursement($voucher1, true);
        $reimbursement2 = $this->makeReimbursement($voucher2, true);

        $reimbursement1->update([
            'iban' => $this->makeIban(),
            'iban_name' => $this->makeIbanName(),
        ]);

        $reimbursement2->update([
            'iban' => $this->makeIban(),
            'iban_name' => $this->makeIbanName(),
        ]);

        // assert no reimbursements visible if they are not approved
        $this->assertSearchIds([], [], $organization);

        // approve first reimbursement and assert only first visible
        $reimbursement1->assign($employee)->approve();
        $this->assertSearchIds([], [$reimbursement1->id], $organization);

        // approve second reimbursement and assert that both are visible
        $reimbursement2->assign($employee)->approve();

        $this->assertSearchIds([], [$reimbursement1->id, $reimbursement2->id], $organization);

        // assert filter by identity id
        $this->assertSearchIds([
            'identity_id' => $identity1->id,
        ], [$reimbursement1->id], $organization);

        $this->assertSearchIds([
            'identity_id' => $identity2->id,
        ], [$reimbursement2->id], $organization);
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

        $organization = $this->makeTestOrganization($this->makeIdentity(), ['bsn_enabled' => true]);
        $employee = $organization->employees()->first();

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

        $reimbursement1->update([
            'iban' => "NL{$ibanPart1}55555",
            'iban_name' => "$namePart1 name",
        ]);

        $reimbursement2->update([
            'iban' => "NL{$ibanPart2}55555",
            'iban_name' => "$namePart2 name",
        ]);

        $reimbursement1->assign($employee)->approve();
        $reimbursement2->assign($employee)->approve();

        // assert filter by q for first reimbursement
        $this->assertSearchIds([
            'q' => $bsnPart1,
        ], [$reimbursement1->id], $organization);

        $this->assertSearchIds([
            'q' => $emailPart1,
        ], [$reimbursement1->id], $organization);

        $this->assertSearchIds([
            'q' => $namePart1,
        ], [$reimbursement1->id], $organization);

        $this->assertSearchIds([
            'q' => $ibanPart1,
        ], [$reimbursement1->id], $organization);

        // assert filter by q for second reimbursement
        $this->assertSearchIds([
            'q' => $bsnPart2,
        ], [$reimbursement2->id], $organization);

        $this->assertSearchIds([
            'q' => $emailPart2,
        ], [$reimbursement2->id], $organization);

        $this->assertSearchIds([
            'q' => $namePart2,
        ], [$reimbursement2->id], $organization);

        $this->assertSearchIds([
            'q' => $ibanPart2,
        ], [$reimbursement2->id], $organization);
    }

    /**
     * @param array $filters
     * @param Organization $organization
     * @return ReimbursementPayoutBankAccountSearch
     */
    private function makeSearch(array $filters, Organization $organization): ReimbursementPayoutBankAccountSearch
    {
        return new ReimbursementPayoutBankAccountSearch($organization, $filters);
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
