<?php

namespace App\Console\Commands;

use App\Events\Vouchers\VoucherExpired;
use App\Events\Vouchers\VoucherExpireSoon;
use App\Models\Voucher;
use App\Scopes\Builders\FundQuery;
use App\Scopes\Builders\VoucherQuery;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class NotifyAboutVoucherExpireCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'forus.voucher:check-expire';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send vouchers expiration warning email 4 weeks before expiration.';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void
    {
        $interval3Weeks = [now(), now()->addWeeks(3)];
        $interval6Weeks = [now()->addWeeks(3)->addDay(), now()->addWeeks(6)];

        $expiredVouchers = $this->getExpiredVouchers();
        $expiringVouchers3Weeks = $this->getExpiringVouchers($interval3Weeks[0], $interval3Weeks[1]);
        $expiringVouchers6Weeks = $this->getExpiringVouchers($interval6Weeks[0], $interval6Weeks[1]);

        foreach ($expiringVouchers3Weeks as $voucher) {
            VoucherExpireSoon::dispatch($voucher);
        }

        foreach ($expiringVouchers6Weeks as $voucher) {
            VoucherExpireSoon::dispatch($voucher);
        }

        foreach ($expiredVouchers as $voucher) {
            VoucherExpired::dispatch($voucher);
        }
    }

    /**
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return Collection
     */
    public function getExpiringVouchers(Carbon $startDate, Carbon $endDate): Collection
    {
        $builder = $this->queryVouchers(
            VoucherQuery::whereNotExpiredAndActive(Voucher::query()),
            Voucher::EVENT_EXPIRING_SOON_BUDGET,
            Voucher::EVENT_EXPIRING_SOON_PRODUCT
        )->whereBetween('expire_at', [
            $startDate->format('Y-m-d'),
            $endDate->format('Y-m-d'),
        ]);

        return $builder->with('fund', 'fund.organization')->get();
    }

    /**
     * @return Collection
     */
    private function getExpiredVouchers(): Collection
    {
        $builder = $this->queryVouchers(
            Voucher::query(),
            Voucher::EVENT_EXPIRED_BUDGET,
            Voucher::EVENT_EXPIRED_PRODUCT,
        )->whereDate('expire_at', now()->subDay()->format('Y-m-d'));

        return $builder->with('fund', 'fund.organization')->get();
    }

    /**
     * @param Builder $builder
     * @param string $budgetEvent
     * @param string $productEvent
     * @return Builder
     */
    protected function queryVouchers(
        Builder $builder,
        string $budgetEvent,
        string $productEvent
    ): Builder {
        $builder->where(function(Builder $builder) use ($budgetEvent, $productEvent) {
            // budget vouchers
            $builder->where(function(Builder $builder) use ($budgetEvent) {
                $builder->whereNull('product_id');
                $builder->whereDoesntHave('logs', function(Builder $builder) use ($budgetEvent) {
                    $builder->where('event', $budgetEvent);
                });
            });

            // product vouchers
            $builder->orWhere(function(Builder $builder) use ($productEvent) {
                $builder->whereNotNull('product_id');
                $builder->whereDoesntHave('transactions');

                $builder->whereDoesntHave('logs', function(Builder $builder) use ($productEvent) {
                    $builder->where('event', $productEvent);
                });
            });
        });

        return $builder
            ->whereNull('parent_id')
            ->whereNull('product_reservation_id')
            ->whereNotNull('identity_address')
            ->whereHas('fund.fund_config.implementation');
    }
}
