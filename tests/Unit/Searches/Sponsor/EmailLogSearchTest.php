<?php

namespace Tests\Unit\Searches\Sponsor;

use App\Mail\Funds\FundRequests\FundRequestCreatedMail;
use App\Mail\Vouchers\VoucherAssignedBudgetMail;
use App\Models\Fund;
use App\Models\FundRequest;
use App\Models\Identity;
use App\Models\Organization;
use App\Searches\Sponsor\EmailLogSearch;
use App\Services\MailDatabaseLoggerService\Models\EmailLog;
use App\Traits\DoesTesting;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\Traits\MakesTestFundRequests;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestOrganizations;
use Tests\Traits\MakesTestVouchers;
use Tests\Unit\Searches\SearchTestCase;

class EmailLogSearchTest extends SearchTestCase
{
    use DoesTesting;
    use MakesTestFunds;
    use MakesTestVouchers;
    use MakesTestFundRequests;
    use MakesTestOrganizations;

    /**
     * @return void
     */
    public function testRequiresIdentityOrFundRequest(): void
    {
        $identity = $this->makeIdentity();
        $organization = $this->makeTestOrganization($identity);

        $this->expectException(HttpException::class);

        $search = new EmailLogSearch([], EmailLog::query(), $organization);
        $search->query();
    }

    /**
     * @return void
     */
    public function testFiltersByIdentityIdOrFundRequestId(): void
    {
        $from = Carbon::now();
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization);

        $identity = $this->makeIdentity($this->makeUniqueEmail());
        // make another identity, voucher for it and fund request and assert that
        // only related to first identity emails are visible
        $identityOther = $this->makeIdentity($this->makeUniqueEmail());

        $this->makeTestVoucher($fund, $identity);
        $this->makeTestVoucher($fund, $identityOther);

        $voucherEmail = $this->getEmailOfTypeQuery($identity->email, VoucherAssignedBudgetMail::class, $from)->first();
        $this->assertNotNull($voucherEmail);

        $this->assertSearchIds([
            'identity_id' => $identity->id,
        ], [$voucherEmail->id], $organization);

        $fundRequest = $this->makeFundRequestForIdentity($fund, $identity);
        $this->makeFundRequestForIdentity($fund, $identityOther);

        $fundRequestEmail = $this->getEmailOfTypeQuery($identity->email, FundRequestCreatedMail::class, $from)->first();
        $this->assertNotNull($fundRequestEmail);

        $this->assertSearchIds([
            'identity_id' => $identity->id,
        ], [$voucherEmail->id, $fundRequestEmail->id], $organization);

        $this->assertSearchIds([
            'fund_request_id' => $fundRequest->id,
        ], [$fundRequestEmail->id], $organization);
    }

    /**
     * @return void
     */
    public function testFiltersByQuery(): void
    {
        $fromNamePart1 = 'match';
        $fromNamePart2 = 'other';

        $fromAddressPart1 = 'first';
        $fromAddressPart2 = 'last';

        $toNamePart1 = 'pretty';
        $toNamePart2 = 'ugly';

        $toAddressPart1 = 'second';
        $toAddressPart2 = 'third';

        $subjectPart1 = 'next';
        $subjectPart2 = 'previous';

        $contentPart1 = 'difficult';
        $contentPart2 = 'easy';

        $from = Carbon::now();
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization);

        $identity = $this->makeIdentity($this->makeUniqueEmail());

        $this->makeTestVoucher($fund, $identity);
        $voucherEmail = $this->getEmailOfTypeQuery($identity->email, VoucherAssignedBudgetMail::class, $from)->first();
        $this->assertNotNull($voucherEmail);

        $voucherEmail->update([
            'from_name' => "$fromNamePart1 name",
            'from_address' => $this->makeUniqueEmail($fromAddressPart1),
            'to_name' => "$toNamePart1 name",
            'to_address' => $this->makeUniqueEmail($toAddressPart1),
            'subject' => "$subjectPart1 subject",
            'content' => "$contentPart1 content",
        ]);

        $this->makeFundRequestForIdentity($fund, $identity);
        $fundRequestEmail = $this->getEmailOfTypeQuery($identity->email, FundRequestCreatedMail::class, $from)->first();
        $this->assertNotNull($fundRequestEmail);

        $fundRequestEmail->update([
            'from_name' => "$fromNamePart2 name",
            'from_address' => $this->makeUniqueEmail($fromAddressPart2),
            'to_name' => "$toNamePart2 name",
            'to_address' => $this->makeUniqueEmail($toAddressPart2),
            'subject' => "$subjectPart2 subject",
            'content' => "$contentPart2 content",
        ]);

        // assert for voucher email
        $this->assertSearchIds([
            'identity_id' => $identity->id,
            'q' => $fromNamePart1,
        ], [$voucherEmail->id], $organization);

        $this->assertSearchIds([
            'identity_id' => $identity->id,
            'q' => $fromAddressPart1,
        ], [$voucherEmail->id], $organization);

        $this->assertSearchIds([
            'identity_id' => $identity->id,
            'q' => $toNamePart1,
        ], [$voucherEmail->id], $organization);

        $this->assertSearchIds([
            'identity_id' => $identity->id,
            'q' => $toAddressPart1,
        ], [$voucherEmail->id], $organization);

        $this->assertSearchIds([
            'identity_id' => $identity->id,
            'q' => $subjectPart1,
        ], [$voucherEmail->id], $organization);

        $this->assertSearchIds([
            'identity_id' => $identity->id,
            'q' => $contentPart1,
        ], [$voucherEmail->id], $organization);

        // assert for fund request email
        $this->assertSearchIds([
            'identity_id' => $identity->id,
            'q' => $fromNamePart2,
        ], [$fundRequestEmail->id], $organization);

        $this->assertSearchIds([
            'identity_id' => $identity->id,
            'q' => $fromAddressPart2,
        ], [$fundRequestEmail->id], $organization);

        $this->assertSearchIds([
            'identity_id' => $identity->id,
            'q' => $toNamePart2,
        ], [$fundRequestEmail->id], $organization);

        $this->assertSearchIds([
            'identity_id' => $identity->id,
            'q' => $toAddressPart2,
        ], [$fundRequestEmail->id], $organization);

        $this->assertSearchIds([
            'identity_id' => $identity->id,
            'q' => $subjectPart2,
        ], [$fundRequestEmail->id], $organization);

        $this->assertSearchIds([
            'identity_id' => $identity->id,
            'q' => $contentPart2,
        ], [$fundRequestEmail->id], $organization);
    }

    /**
     * @param array $filters
     * @param Organization $organization
     * @return EmailLogSearch
     */
    private function makeSearch(array $filters, Organization $organization): EmailLogSearch
    {
        return new EmailLogSearch($filters, EmailLog::query(), $organization);
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
