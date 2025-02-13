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
     * Run the command to check for expired vouchers and send notifications.
     *
     * @return void
     */
    public function handle(): void
    {
        // Get vouchers that have expired but are still marked as active.
        // Only consider vouchers that expired within the last week.
        $query = VoucherQuery::whereExpiredButActive(Voucher::query())
            ->where('expire_at', '>=', now()->subWeek());

        // Fetch the vouchers
        $vouchers = $this->queryVouchers($query)->get();

        foreach ($vouchers as $voucher) {
            // Check if an expiration event has already been logged.
            $hasExpiredLog = $voucher->logs()->where(function (Builder $builder) {
                $builder->whereIn('event', [
                    Voucher::EVENT_EXPIRED_BUDGET,
                    Voucher::EVENT_EXPIRED_PRODUCT,
                ]);
            })->exists();

            // If no log exists and the voucher has expired, dispatch the expiration event.
            if (!$hasExpiredLog && $voucher->expire_at->isPast()) {
                VoucherExpired::dispatch($voucher);
            }
        }
    }

    /**
     * Filter vouchers to get only the ones that qualify for expiration notifications.
     *
     * @param Builder|Relation|Voucher $builder
     * @return Builder|Relation|Voucher
     */
    protected function queryVouchers(Builder|Relation|Voucher $builder): Builder|Relation|Voucher
    {
        $builder->where(function (Builder $builder) {
            // Include budget vouchers (vouchers without products).
            $builder->where(function (Builder $builder) {
                $builder->whereNull('product_id');
            });

            // Include product vouchers without transactions
            $builder->orWhere(function (Builder $builder) {
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
