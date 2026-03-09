<?php

namespace Tests\Unit\Searches\Sponsor\PayoutBankAccounts;

use App\Searches\Sponsor\PayoutBankAccounts\ReimbursementPayoutBankAccountSearch;
use App\Traits\DoesTesting;
use Tests\Traits\MakesTestOrganizations;
use Tests\Unit\Searches\SearchTestCase;

class ReimbursementPayoutBankAccountSearchTest extends SearchTestCase
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

        $search = new ReimbursementPayoutBankAccountSearch($organization, []);

        $this->assertQueryBuilds($search->query());
    }
}
