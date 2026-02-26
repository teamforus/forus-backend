<?php

namespace Tests\Unit\Searches\Sponsor;

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
}
