<?php

namespace Tests\Unit\Searches\Sponsor;

use App\Models\Organization;
use App\Searches\Sponsor\EmailLogSearch;
use App\Services\MailDatabaseLoggerService\Models\EmailLog;
use App\Traits\DoesTesting;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\Traits\MakesTestOrganizations;
use Tests\Unit\Searches\SearchTestCase;

class EmailLogSearchTest extends SearchTestCase
{
    use DoesTesting;
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
