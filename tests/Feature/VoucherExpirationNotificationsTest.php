<?php

namespace Tests\Feature;

use App\Models\Voucher;
use App\Services\EventLogService\Models\EventLog;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestOrganizations;
use Tests\Traits\MakesVoucherTransaction;

class VoucherExpirationNotificationsTest extends TestCase
{
    use MakesVoucherTransaction;
    use WithFaker;
    use DatabaseTransactions;
    use MakesTestFunds;
    use MakesTestOrganizations;

    /**
     * Test that no "expire soon" notification is sent before 6 weeks.
     *
     * @return void
     */
    public function testExpireSoonNotificationIsNotSentBefore6Weeks(): void
    {
        $voucher = $this->makeVoucherForTest();
        $expireDate = $voucher->expire_at->clone();

        $this->travelTo($expireDate->clone()->subWeeks(6)->subDay());
        $this->artisan('forus.voucher:check-expire-soon');

        self::assertEquals(
            0,
            $voucher->fresh()->logs()->where('event', Voucher::EVENT_EXPIRING_SOON_BUDGET)->count(),
            'Expire soon notification should not be sent before 6 weeks.',
        );
    }

    /**
     * Test that an "expire soon" notification is sent exactly at 6 weeks and not repeated after.
     *
     * @return void
     */
    public function testExpireSoonNotificationIsSentOnceAt6Weeks(): void
    {
        $voucher = $this->makeVoucherForTest();
        $expireDate = $voucher->expire_at->clone();

        $this->travelTo($expireDate->clone()->subWeeks(6));
        $this->artisan('forus.voucher:check-expire-soon');

        $this->travelTo($expireDate->clone()->subWeeks(6)->addDay());
        $this->artisan('forus.voucher:check-expire-soon');

        $this->travelTo($expireDate->clone()->subWeeks(6)->addDays(2));
        $this->artisan('forus.voucher:check-expire-soon');

        self::assertEquals(
            1,
            $voucher->fresh()->logs()->where('event', Voucher::EVENT_EXPIRING_SOON_BUDGET)->count(),
            'Expire soon notification should be sent only once at 6 weeks.',
        );
    }

    /**
     * Test that an "expire soon" notification is sent exactly at 3 weeks and not repeated after.
     *
     * @return void
     */
    public function testExpireSoonNotificationIsSentOnceAt3Weeks(): void
    {
        $voucher = $this->makeVoucherForTest();
        $expireDate = $voucher->expire_at->clone();

        $this->travelTo($expireDate->clone()->subWeeks(3));
        $this->artisan('forus.voucher:check-expire-soon');

        $this->travelTo($expireDate->clone()->subWeeks(3)->addDays(2));
        $this->artisan('forus.voucher:check-expire-soon');

        self::assertEquals(
            1,
            $voucher->fresh()->logs()->where('event', Voucher::EVENT_EXPIRING_SOON_BUDGET)->count(),
            'Expire soon notification should be sent only once at 3 weeks.',
        );
    }

    /**
     * Test that "expire soon" notifications are sent exactly at 6 and 3 weeks.
     *
     * @return void
     */
    public function testExpireSoonNotificationsAreSentOnceAt6And3Weeks(): void
    {
        $voucher = $this->makeVoucherForTest();
        $date = $voucher->expire_at->clone()->subWeeks(8);

        while ($date->isBefore($voucher->expire_at->clone()->addWeek())) {
            $date->addDay();
            $this->travelTo($date);
            $this->artisan('forus.voucher:check-expire-soon');
        }

        /** @var EventLog[]|Collection $events */
        $events = $voucher->fresh()->logs()->where('event', Voucher::EVENT_EXPIRING_SOON_BUDGET)->get();

        self::assertEquals(
            2,
            $events->count(),
            'Expire soon notifications should be sent exactly at 6 and 3 weeks before expiration.',
        );

        self::assertTrue(
            $events[0]->created_at->isSameDay($voucher->expire_at->clone()->subWeeks(6)),
            'First expire soon notification should be at 6 weeks before expiration.'
        );

        self::assertTrue(
            $events[1]->created_at->isSameDay($voucher->expire_at->clone()->subWeeks(3)),
            'Second expire soon notification should be at 3 weeks before expiration.'
        );
    }

    /**
     * Test that an "expired" notification is sent exactly when the voucher expires.
     *
     * @return void
     */
    public function testExpiredNotificationIsSentOnceOnExpiration(): void
    {
        $voucher = $this->makeVoucherForTest();
        $expireDate = $voucher->expire_at->clone();

        $this->travelTo($expireDate->clone());
        $this->artisan('forus.voucher:check-expired');

        $this->travelTo($expireDate->clone()->addDay());
        $this->artisan('forus.voucher:check-expired');

        $this->travelTo($expireDate->clone()->addDays(2));
        $this->artisan('forus.voucher:check-expired');

        self::assertEquals(
            1,
            $voucher->fresh()->logs()->where('event', Voucher::EVENT_EXPIRED_BUDGET)->count(),
            'Expired notification should be sent only once when the voucher expires.',
        );
    }

    /**
     * Create a test voucher.
     *
     * @return Voucher
     */
    protected function makeVoucherForTest(): voucher
    {
        $identityRequester = $this->makeIdentity($this->makeUniqueEmail());
        $identitySponsor = $this->makeIdentity($this->makeUniqueEmail());

        $organization = $this->makeTestOrganization($identitySponsor);
        $fund = $this->makeTestFund($organization);
        $expireDate = $fund->end_date->clone();

        $response = $this->postJson("/api/v1/platform/organizations/$organization->id/sponsor/vouchers", [
            "email" => $identityRequester->email,
            "amount" => "100",
            "records" => [],
            "fund_id" => $fund->id,
            "expire_at" => $expireDate->format('Y-m-d'),
            "activate" => 1,
            "assign_by_type" => "email",
        ], $this->makeApiHeaders($this->makeIdentityProxy($identitySponsor)));

        $response->assertSuccessful();

        return $fund->vouchers[0];
    }
}
