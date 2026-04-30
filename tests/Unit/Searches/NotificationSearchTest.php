<?php

namespace Tests\Unit\Searches;

use App\Events\Funds\FundCreatedEvent;
use App\Models\Identity;
use App\Models\Notification;
use App\Notifications\Organizations\Funds\FundCreatedNotification;
use App\Searches\NotificationSearch;
use App\Traits\DoesTesting;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestOrganizations;

class NotificationSearchTest extends SearchTestCase
{
    use DoesTesting;
    use MakesTestFunds;
    use MakesTestOrganizations;

    /**
     * @return void
     */
    public function testQueryBuilds(): void
    {
        $search = new NotificationSearch([], Notification::query());

        $this->assertQueryBuilds($search->query());
    }

    /**
     * Create organization, it will add employee and notification about employee created.
     * Next create fund and notification that fund created, it will have organization_id.
     * Now we can check filter by organization_id.
     * @return void
     */
    public function testFiltersByOrganizationId(): void
    {
        $identity1 = $this->makeIdentity();
        $identity2 = $this->makeIdentity();
        $organization1 = $this->makeTestOrganization($identity1);
        $organization2 = $this->makeTestOrganization($identity2);

        $fund1 = $this->makeTestFund($organization1);
        FundCreatedEvent::dispatch($fund1);

        $fund2 = $this->makeTestFund($organization2);
        FundCreatedEvent::dispatch($fund2);

        // assert that all notifications visible for identity
        $this->assertSearchIds([], $identity1->notifications()->pluck('id')->toArray(), $identity1);
        $this->assertSearchIds([], $identity2->notifications()->pluck('id')->toArray(), $identity2);

        // get notification with organization_id for first identity
        $notification1 = $identity1
            ->notifications()
            ->where('key', FundCreatedNotification::getKey())
            ->first();

        $this->assertNotNull($notification1);

        // get notification with organization_id for second identity
        $notification2 = $identity2
            ->notifications()
            ->where('key', FundCreatedNotification::getKey())
            ->first();

        $this->assertNotNull($notification2);

        $this->assertSearchIds(['organization_id' => $organization1->id], [$notification1->id], $identity1);
        $this->assertSearchIds(['organization_id' => $organization2->id], [$notification2->id], $identity2);
    }

    /**
     * Create organization, it will add employee and notification about employee created.
     * Next create fund and notification that fund created.
     * Check if seen is "false" we can see all not read notifications.
     * Then update fund created notifications as read.
     * Now check with seen "false" we don't get fund created notification.
     * After check with seen "true" that we fetch only fund created notification.
     * @return void
     */
    public function testFiltersBySeen(): void
    {
        $identity1 = $this->makeIdentity();
        $identity2 = $this->makeIdentity();
        $organization1 = $this->makeTestOrganization($identity1);
        $organization2 = $this->makeTestOrganization($identity2);

        $fund1 = $this->makeTestFund($organization1);
        FundCreatedEvent::dispatch($fund1);

        $fund2 = $this->makeTestFund($organization2);
        FundCreatedEvent::dispatch($fund2);

        // assert that all notifications visible for identity
        $allNotifications1 = $identity1->notifications()->pluck('id')->toArray();
        $allNotifications2 = $identity2->notifications()->pluck('id')->toArray();
        $this->assertSearchIds([], $allNotifications1, $identity1);
        $this->assertSearchIds([], $allNotifications2, $identity2);

        // get notification with organization_id for first identity
        $notification1 = $identity1
            ->notifications()
            ->where('key', FundCreatedNotification::getKey())
            ->first();

        $this->assertNotNull($notification1);

        // get notification with organization_id for second identity
        $notification2 = $identity2
            ->notifications()
            ->where('key', FundCreatedNotification::getKey())
            ->first();

        $this->assertNotNull($notification2);

        $this->assertSearchIds(['seen' => false], $allNotifications1, $identity1);
        $this->assertSearchIds(['seen' => false], $allNotifications2, $identity2);

        $notification1->update(['read_at' => now()]);
        $notification2->update(['read_at' => now()]);

        $this->assertSearchIds(
            ['seen' => false],
            array_filter($allNotifications1, fn (string $id) => $notification1->id !== $id),
            $identity1
        );

        $this->assertSearchIds(
            ['seen' => false],
            array_filter($allNotifications2, fn (string $id) => $notification2->id !== $id),
            $identity2
        );

        $this->assertSearchIds(['seen' => true], [$notification1->id], $identity1);
        $this->assertSearchIds(['seen' => true], [$notification2->id], $identity2);
    }

    /**
     * @param array $filters
     * @param Identity $identity
     * @return NotificationSearch
     */
    private function makeSearch(array $filters, Identity $identity): NotificationSearch
    {
        return new NotificationSearch($filters, $identity->notifications());
    }

    /**
     * @param array $filters
     * @param array $expectedIds
     * @param Identity $identity
     * @return void
     */
    private function assertSearchIds(array $filters, array $expectedIds, Identity $identity): void
    {
        $expected = collect($expectedIds)->sort()->values()->toArray();
        $search = $this->makeSearch($filters, $identity);
        $actual = collect($search->query()->pluck('id')->toArray())->sort()->values()->toArray();

        $this->assertSame($expected, $actual);
    }
}
