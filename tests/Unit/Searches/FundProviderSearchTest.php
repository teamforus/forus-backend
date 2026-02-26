<?php

namespace Tests\Unit\Searches;

use App\Models\FundProvider;
use App\Searches\FundProviderSearch;
use App\Traits\DoesTesting;
use Tests\Traits\MakesTestOrganizations;

class FundProviderSearchTest extends SearchTestCase
{
    use DoesTesting;
    use MakesTestOrganizations;

    /**
     * @return void
     */
    public function testQueryBuilds(): void
    {
        $identity = $this->makeIdentity();
        $organization = $this->makeTestOrganization($identity);

        $search = new FundProviderSearch([], FundProvider::query(), $organization);

        $this->assertQueryBuilds($search->query());
    }
}
