<?php

namespace Tests\Unit\Searches\Sponsor\PayoutBankAccounts;

use App\Searches\Sponsor\PayoutBankAccounts\FundRequestPayoutBankAccountSearch;
use App\Traits\DoesTesting;
use Tests\Traits\MakesTestOrganizations;
use Tests\Unit\Searches\SearchTestCase;

class FundRequestPayoutBankAccountSearchTest extends SearchTestCase
{
    use DoesTesting;
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
}
