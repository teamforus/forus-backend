<?php

namespace App\Console\Commands;

use App\Events\Vouchers\VoucherExpired;
use App\Events\Vouchers\VoucherExpireSoon;
use App\Models\Voucher;
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
        $interval3Weeks = [now()->startOfDay(), now()->addWeeks(3)->endOfDay()];
        $interval6Weeks = [now()->startOfDay(), now()->addWeeks(6)->endOfDay()];

        foreach ($this->getExpiringVouchers($interval3Weeks) as $voucher) {
            VoucherExpireSoon::dispatch($voucher);
        }

        foreach ($this->getExpiringVouchers($interval6Weeks) as $voucher) {
            VoucherExpireSoon::dispatch($voucher);
        }

        foreach ($this->getExpiredVouchers() as $voucher) {
            VoucherExpired::dispatch($voucher);
        }
    }

    /**
     * @param Carbon[] $between
     * @return Collection
     */
    public function getExpiringVouchers(array $between): Collection
    {
        $builder = $this->queryVouchers(
            VoucherQuery::whereNotExpiredAndActive(Voucher::query()),
            Voucher::EVENT_EXPIRING_SOON_BUDGET,
            Voucher::EVENT_EXPIRING_SOON_PRODUCT,
            $between,
        )->whereBetween('expire_at', $between);

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
     * @param Builder|Voucher $builder
     * @param string $budgetEvent
     * @param string $productEvent
     * @param Carbon[] $logBetween
     * @return Builder
     */
    protected function queryVouchers(
        Builder|Voucher $builder,
        string $budgetEvent,
        string $productEvent,
        array $logBetween = null,
    ): Builder {
        $builder->where(function(Builder $builder) use ($budgetEvent, $productEvent, $logBetween) {
            // budget vouchers
            $builder->where(function(Builder $builder) use ($budgetEvent, $logBetween) {
                $builder->whereNull('product_id');
                $builder->whereDoesntHave('logs', function(Builder $builder) use ($budgetEvent, $logBetween) {
                    $builder->where('event', $budgetEvent);
                    $logBetween && $builder->whereBetween('created_at', $logBetween);
                });
            });

            // product vouchers
            $builder->orWhere(function(Builder $builder) use ($productEvent, $logBetween) {
                $builder->whereNotNull('product_id');
                $builder->whereDoesntHave('transactions');

                $builder->whereDoesntHave('logs', function(Builder $builder) use ($productEvent, $logBetween) {
                    $builder->where('event', $productEvent);
                    $logBetween && $builder->whereBetween('created_at', $logBetween);
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
