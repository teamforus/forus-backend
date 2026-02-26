<?php

namespace Tests\Unit\Searches\Sponsor;

use App\Searches\Sponsor\FundIdentitiesSearch;
use App\Traits\DoesTesting;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestOrganizations;
use Tests\Unit\Searches\SearchTestCase;

class FundIdentitiesSearchTest extends SearchTestCase
{
    use DoesTesting;
    use MakesTestFunds;
    use MakesTestOrganizations;

    /**
     * @return void
     */
    public function testQueryBuilds(): void
    {
        $identity = $this->makeIdentity();
        $organization = $this->makeTestOrganization($identity);
        $fund = $this->makeTestFund($organization);

        $search = new FundIdentitiesSearch([], $fund);

        $this->assertQueryBuilds($search->query());
    }
}
