<?php

namespace Tests\Unit\Searches;

use App\Models\Notification;
use App\Searches\NotificationSearch;

class NotificationSearchTest extends SearchTestCase
{
    /**
     * @return void
     */
    public function testQueryBuilds(): void
    {
        $search = new NotificationSearch([], Notification::query());

        $this->assertQueryBuilds($search->query());
    }
}
