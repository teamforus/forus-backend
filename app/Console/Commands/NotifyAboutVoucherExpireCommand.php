<?php

namespace App\Console\Commands;

use App\Events\Vouchers\VoucherExpired;
use App\Events\Vouchers\VoucherExpireSoon;
use App\Models\Voucher;
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
        $expiredVouchers = $this->getExpiredVouchers();
        $expiringVouchers = $this->getExpiringVouchers();

        foreach ($expiringVouchers as $voucher) {
            VoucherExpireSoon::dispatch($voucher);
        }

        foreach ($expiredVouchers as $voucher) {
            VoucherExpired::dispatch($voucher);
        }
    }

    /**
     * @param int $days_before_expiration
     * @return Collection
     */
    public function getExpiringVouchers(int $days_before_expiration = 4 * 7): Collection
    {
        $builder = Voucher::where(function(Builder $builder) {
            // budget vouchers
            $builder->whereNull('product_id');
            $builder->whereDoesntHave('logs', function(Builder $builder) {
                $builder->where('event', Voucher::EVENT_EXPIRING_SOON_BUDGET);
            });
        })->orWhere(function(Builder $builder) {
            // product vouchers
            $builder->whereNotNull('product_id');
            $builder->whereDoesntHave('transactions');
            $builder->whereDoesntHave('logs', function(Builder $builder) {
                $builder->where('event', Voucher::EVENT_EXPIRING_SOON_PRODUCT);
            });
        });

        $builder->with('fund', 'fund.organization');
        $builder->whereNotNull('identity_address');
        $builder->where('expire_at', '<', now()->addDays($days_before_expiration)->startOfDay());
        $builder->where('expire_at', '>', now()->subDay()->startOfDay());

        return $builder->get();
    }

    /**
     * @return Builder[]|\Illuminate\Database\Eloquent\Collection
     */
    private function getExpiredVouchers(): Collection
    {
        return Voucher::where(function(Builder $builder) {
            // budget vouchers
            $builder->whereNull('product_id');
            $builder->where('expire_at', '<', now());
            $builder->whereDoesntHave('logs', function(Builder $builder) {
                $builder->where('event', Voucher::EVENT_EXPIRED_BUDGET);
            });
        })->orWhere(function(Builder $builder) {
            // product vouchers
            $builder->whereNotNull('product_id');
            $builder->where('expire_at', '<', now());
            $builder->whereDoesntHave('transactions');
            $builder->whereDoesntHave('logs', function(Builder $builder) {
                $builder->where('event', Voucher::EVENT_EXPIRED_PRODUCT);
            });
        })->get();
    }
}
