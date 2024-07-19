<?php

namespace Tests\Feature;

use App\Models\Fund;
use App\Models\Organization;
use App\Models\SystemNotification;
use App\Models\Voucher;
use Carbon\Carbon;
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

    public function testVoucherExpireSoonNotificationSent(): void
    {
        $identityRequester = $this->makeIdentity($this->makeUniqueEmail());
        $identitySponsor = $this->makeIdentity($this->makeUniqueEmail());

        $organization = $this->makeTestOrganization($identitySponsor);

        $fund = $this->makeTestFund($organization);
        $fund->getOrCreateTopUp()->transactions()->create(['amount' => 1000000]);

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
        $voucher = $fund->vouchers[0];

        self::assertEquals(
            $expireDate->format('Y-m-d'),
            $voucher->expire_at->format('Y-m-d'),
        );

        $this->assertExpireSoonNotificationSent($voucher->fresh(), $expireDate->clone(), 6);
        $this->assertExpireSoonNotificationSent($voucher->fresh(), $expireDate->clone(), 6);
        $this->assertLastExpireSoonDateIsToday($organization, $fund, $expireDate->clone(), 6);

        $this->assertExpireSoonNotificationSent($voucher->fresh(), $expireDate->clone(), 3);
        $this->assertExpireSoonNotificationSent($voucher->fresh(), $expireDate->clone(), 3);
        $this->assertLastExpireSoonDateIsToday($organization, $fund, $expireDate->clone(), 3);

        self::assertTrue(
            $voucher->fresh()->logs->where('event', 'expiring_soon_budget')->count() == 2,
            'Failed to assert that no duplicated notifications sent.',
        );

        $this->assertVoucherExpired($voucher->fresh());
    }

    /**
     * @param Organization $organization
     * @param Fund $fund
     * @param Carbon $date
     * @param int $weeks
     * @return void
     */
    protected function assertLastExpireSoonDateIsToday(
        Organization $organization,
        Fund $fund,
        Carbon $date,
        int $weeks
    ): void {
        $systemNotification = SystemNotification::query()
            ->where('key', 'notifications_identities.voucher_expire_soon_budget')
            ->first();

        $response = $this->getJson(implode('', [
                "/api/v1/platform/organizations/$organization->id",
                "/implementations/{$fund->fund_config->implementation_id}",
                "/system-notifications/$systemNotification->id",
            ]) . "?fund_id=$fund->id", $this->makeApiHeaders($this->makeIdentityProxy($organization->identity)));

        $response->assertSuccessful();

        self::assertEquals(
            $date->subWeeks($weeks)->format('Y-m-d'),
            $response->json('data.last_sent_date'),
            'Failed to assert that last sent `voucher_expire_soon_budget` is correct',
        );
    }

    /**
     * @param Voucher $voucher
     * @param Carbon $date
     * @param int $weeks
     * @return void
     */
    protected function assertExpireSoonNotificationSent(Voucher $voucher, Carbon $date, int $weeks): void
    {
        $this->travelTo($date->subWeeks($weeks));
        $this->artisan('forus.voucher:check-expire');

        $systemNotification = SystemNotification::query()
            ->where('key', 'notifications_identities.voucher_expire_soon_budget')
            ->first();

        self::assertTrue(
            $voucher->logs[$voucher->logs->count() - 1]->event === 'expiring_soon_budget',
            "Expire soon event not fired for $weeks weeks.",
        );

        self::assertEquals(
            $systemNotification->getLastSentDate((array) $voucher->fund_id)?->format('Y-m-d'),
            now()->format('Y-m-d'),
            "Expire soon notification failed for $weeks weeks.",
        );
    }

    /**
     * @param Voucher $voucher
     * @return void
     */
    protected function assertVoucherExpired(Voucher $voucher): void
    {
        $this->travelTo($voucher->expire_at->clone()->addDay());
        $this->artisan('forus.voucher:check-expire');

        self::assertTrue(
            $voucher->logs[$voucher->logs->count() - 1]->event === 'expired',
            'Expired event not fired for test fund.',
        );
    }
}
