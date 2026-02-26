<?php

namespace Tests\Unit\Searches\Sponsor\PayoutBankAccounts;

use App\Searches\Sponsor\PayoutBankAccounts\ProfilePayoutBankAccountSearch;
use App\Traits\DoesTesting;
use Tests\Traits\MakesTestOrganizations;
use Tests\Unit\Searches\SearchTestCase;

class ProfilePayoutBankAccountSearchTest extends SearchTestCase
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

        $search = new ProfilePayoutBankAccountSearch($organization, []);

        $this->assertQueryBuilds($search->query());
    }
}
