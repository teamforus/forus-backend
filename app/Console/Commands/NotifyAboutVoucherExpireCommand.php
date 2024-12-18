<?php

namespace App\Console\Commands;

use App\Events\Vouchers\VoucherExpired;
use App\Events\Vouchers\VoucherExpireSoon;
use App\Models\Voucher;
use App\Scopes\Builders\VoucherQuery;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

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
        /** @var Voucher[] $vouchers */
        $vouchers = $this->queryVouchers(VoucherQuery::whereNotExpiredAndActive(Voucher::query()))->get();

        foreach ($vouchers as $voucher) {
            $expireInWeeks = ceil(now()->diffInWeeks($voucher->expire_at));
            $expireInDays = ceil(now()->diffInDays($voucher->expire_at));

            $has6weeksLogs = $voucher->logs()->where(function(Builder $builder) use ($voucher) {
                $builder->whereIn('event', [
                    Voucher::EVENT_EXPIRING_SOON_BUDGET,
                    Voucher::EVENT_EXPIRING_SOON_PRODUCT,
                ]);
                $builder->whereBetween('created_at', [
                    $voucher->expire_at->clone()->subWeeks(6)->startOfDay(),
                    $voucher->expire_at->clone()->subWeeks(3)->startOfDay(),
                ]);
            })->exists();

            if (!$has6weeksLogs && ($expireInWeeks <= 6 && $expireInWeeks > 3) ) {
                VoucherExpireSoon::dispatch($voucher);
            }

            $has3weeksLogs = $voucher->logs()->where(function(Builder $builder) use ($voucher) {
                $builder->whereIn('event', [
                    Voucher::EVENT_EXPIRING_SOON_BUDGET,
                    Voucher::EVENT_EXPIRING_SOON_PRODUCT,
                ]);
                $builder->whereBetween('created_at', [
                    $voucher->expire_at->clone()->subWeeks(3)->startOfDay(),
                    $voucher->expire_at->clone()->endOfDay(),
                ]);
            })->exists();

            if (!$has3weeksLogs && ($expireInWeeks <= 3 && $expireInWeeks > 0) ) {
                VoucherExpireSoon::dispatch($voucher);
            }

            $hasExpiredLog = $voucher->logs()->where(function(Builder $builder) use ($voucher) {
                $builder->whereIn('event', [
                    Voucher::EVENT_EXPIRED_BUDGET,
                    Voucher::EVENT_EXPIRED_PRODUCT,
                ]);
            })->exists();

            if (!$hasExpiredLog && ($expireInDays <= 0) ) {
                VoucherExpired::dispatch($voucher);
            }
        }
    }

    /**
     * @param Builder|Relation|Voucher $builder
     * @return Builder|Relation|Voucher
     */
    protected function queryVouchers(Builder|Relation|Voucher $builder): Builder|Relation|Voucher
    {
        $builder->where(function(Builder $builder) {
            // budget vouchers
            $builder->where(function(Builder $builder) {
                $builder->whereNull('product_id');
            });

            // product vouchers
            $builder->orWhere(function(Builder $builder) {
                $builder->whereNotNull('product_id');
                $builder->whereDoesntHave('transactions');
            });
        });

        return $builder
            ->whereNull('parent_id')
            ->where('voucher_type', Voucher::VOUCHER_TYPE_VOUCHER)
            ->whereNotNull('identity_id')
            ->whereHas('fund.fund_config.implementation');
    }
}
