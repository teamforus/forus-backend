<?php

namespace App\Console\Commands;

use App\Events\Vouchers\VoucherExpired;
use App\Events\Vouchers\VoucherExpiring;
use App\Models\Voucher;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

class CheckVoucherExpirationCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'forus.voucher.expiration:check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $expiredVouchers = $this->getExpiredVouchers();
        $expiringVouchers = $this->getExpiringVouchers(14);

        foreach ($expiringVouchers as $voucher) {
            VoucherExpiring::dispatch($voucher);
        }

        foreach ($expiredVouchers as $voucher) {
            VoucherExpired::dispatch($voucher);
        }
    }

    /**
     * @param int $days_before_expiration
     * @return Builder[]|\Illuminate\Database\Eloquent\Collection
     */
    public function getExpiringVouchers($days_before_expiration = 14) {
        return Voucher::where(function(Builder $builder) use ($days_before_expiration) {
            // budget vouchers
            $builder->whereNull('product_id');
            $builder->where('expire_at', '<', now()->addDays($days_before_expiration));
            $builder->whereDoesntHave('logs', function(Builder $builder) {
                $builder->where('event', Voucher::EVENT_EXPIRING_SOON_BUDGET);
            });
        })->orWhere(function(Builder $builder) use ($days_before_expiration) {
            // product vouchers
            $builder->whereNotNull('product_id');
            $builder->where('expire_at', '<', now()->addDays($days_before_expiration));
            $builder->whereDoesntHave('transactions');
            $builder->whereDoesntHave('logs', function(Builder $builder) {
                $builder->where('event', Voucher::EVENT_EXPIRING_SOON_PRODUCT);
            });
        })->get();
    }

    /**
     * @return Builder[]|\Illuminate\Database\Eloquent\Collection
     */
    private function getExpiredVouchers() {
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
