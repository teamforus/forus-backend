<?php

namespace Tests\Unit\Searches\Sponsor;

use App\Models\Identity;
use App\Searches\Sponsor\IdentitiesSearch;
use InvalidArgumentException;
use Tests\Unit\Searches\SearchTestCase;

class IdentitiesSearchTest extends SearchTestCase
{
    /**
     * @return void
     */
    public function testRequiresOrganizationId(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $search = new IdentitiesSearch([], Identity::query());

        $search->query();
    }
}
