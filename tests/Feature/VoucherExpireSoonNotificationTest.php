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

class VoucherExpireSoonNotificationTest extends TestCase
{
    use MakesVoucherTransaction;
    use WithFaker;
    use DatabaseTransactions;
    use MakesTestFunds;
    use MakesTestOrganizations;

    public function testNoVoucherExpireNotificationSentToSoon(): void
    {
        $voucher = $this->makeVoucherForTest();
        $expireDate = $voucher->expire_at->clone();

        $this->travelTo($expireDate->clone()->subWeeks(6)->subDay());
        $this->artisan('forus.voucher:check-expire-soon');

        self::assertEquals(
            0,
            $voucher->fresh()->logs()->where('event', Voucher::EVENT_EXPIRING_SOON_BUDGET)->count(),
            'No expire soon notification was sent before time.',
        );

        self::assertEquals(
            0,
            $voucher->fresh()->logs()->where('event', Voucher::EVENT_EXPIRED_BUDGET)->count(),
            'No expired notification sent before time.',
        );
    }

    /**
     * @return void
     */
    public function testOnlyOneExpireSoonVoucherNotificationSentFor6Weeks(): void
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
            'Only one expire soon notification sent for 6 weeks.',
        );

        self::assertEquals(
            0,
            $voucher->fresh()->logs()->where('event', Voucher::EVENT_EXPIRED_BUDGET)->count(),
            'No expired notification sent before time.',
        );
    }

    /**
     * @return void
     */
    public function testOnlyOneExpireSoonVoucherNotificationSentFor3Weeks(): void
    {
        $voucher = $this->makeVoucherForTest();
        $expireDate = $voucher->expire_at->clone();

        $this->travelTo($expireDate->clone()->subWeeks(3));
        $this->artisan('forus.voucher:check-expire-soon');

        $this->travelTo($expireDate->clone()->subWeeks(3)->addDays(2));
        $this->artisan('forus.voucher:check-expire-soon');

        $this->travelTo($expireDate->clone()->addDay());
        $this->artisan('forus.voucher:check-expired');

        self::assertEquals(
            1,
            $voucher->fresh()->logs()->where('event', Voucher::EVENT_EXPIRING_SOON_BUDGET)->count(),
            'Only one expire soon notification sent for 3 weeks.',
        );

        self::assertEquals(
            1,
            $voucher->fresh()->logs()->where('event', Voucher::EVENT_EXPIRED_BUDGET)->count(),
            'No expired notification sent before time.',
        );
    }

    /**
     * @return void
     */
    public function testOnlyOneExpiredAndExpireSoonNotificationSentFor3and6Weeks(): void
    {
        $voucher = $this->makeVoucherForTest();
        $date = $voucher->expire_at->clone()->subWeeks(8);

        while ($date->isBefore($voucher->expire_at->clone()->addWeek())) {
            $date->addDay();
            $this->travelTo($date);
            $this->artisan('forus.voucher:check-expired');
            $this->artisan('forus.voucher:check-expire-soon');
        }

        /** @var EventLog[]|Collection $events */
        $events = $voucher->fresh()->logs()->where('event', Voucher::EVENT_EXPIRING_SOON_BUDGET)->get();

        self::assertEquals(
            2,
            $events->count(),
            'Only 2 expire soon notifications sent in total.'
        );

        self::assertEquals(
            1,
            $voucher->fresh()->logs()->where('event', Voucher::EVENT_EXPIRED_BUDGET)->count(),
            'Only one expired notification sent in total.'
        );

        self::assertTrue($events[0]->created_at->isSameDay($voucher->expire_at->clone()->subWeeks(6)));
        self::assertTrue($events[1]->created_at->isSameDay($voucher->expire_at->clone()->subWeeks(3)));
    }

    /**
     * @return Voucher
     */
    protected function makeVoucherForTest(): voucher {
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
            "assign_by_type" => "email"
        ], $this->makeApiHeaders($this->makeIdentityProxy($identitySponsor)));

        $response->assertSuccessful();

        return $fund->vouchers[0];
    }
}
