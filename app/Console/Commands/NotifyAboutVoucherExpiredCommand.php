<?php

namespace App\Console\Commands;

use App\Events\Vouchers\VoucherExpired;
use App\Models\Voucher;
use App\Scopes\Builders\VoucherQuery;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

class NotifyAboutVoucherExpiredCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'forus.voucher:check-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send vouchers expired notification.';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void
    {
        $query = VoucherQuery::whereExpiredButActive(Voucher::query())
            ->where('expire_at', '>=', now()->subWeek());

        /** @var Voucher[] $vouchers */
        $vouchers = $this->queryVouchers($query)->get();

        foreach ($vouchers as $voucher) {
            $expireInDays = ceil(now()->diffInDays($voucher->expire_at));

            $hasExpiredLog = $voucher->logs()->where(function(Builder $builder) {
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
