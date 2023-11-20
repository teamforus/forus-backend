<?php

namespace App\Console\Commands;

use App\Models\ProductReservation;
use App\Models\ReservationExtraPayment;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Throwable;

class ReservationExtraPaymentExpireCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reservation:extra-payments-expire';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set canceled reservation if extra payment time is expired';

    /**
     * Execute the console command.
     *
     * @return void
     * @throws Throwable
     */
    public function handle(): void
    {
        $expiresAt = now()->subMinutes(60);

        ProductReservation::query()
            ->where('state', ProductReservation::STATE_WAITING)
            ->where(function (Builder $query) use ($expiresAt) {
                $query->whereHas('extra_payment', function (Builder $builder) use ($expiresAt) {
                    $builder->where('created_at', '<=', $expiresAt);
                    $builder->where('state', '!=', ReservationExtraPayment::STATE_PAID);
                })->orWhere(function (Builder $builder) use ($expiresAt) {
                    $builder->doesntHave('extra_payment');
                    $builder->where('created_at', '<=', $expiresAt);
                });
            })
            ->get()
            ->each(function (ProductReservation $productReservation) {
                return $productReservation->cancelByExtraPaymentExpired();
            });
    }
}
