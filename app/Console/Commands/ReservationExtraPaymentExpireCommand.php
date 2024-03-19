<?php

namespace App\Console\Commands;

use App\Models\ProductReservation;
use App\Scopes\Builders\ProductReservationQuery;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Config;
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
    protected $description = 'Cancel the reservations where extra payment time is expired.';

    /**
     * @var int|mixed
     */
    private int $waitingTime;

    public function getReservationsWithExpiredExtraPaymentsQuery(): Builder|ProductReservation
    {
        return ProductReservation::query()
            ->where('state', ProductReservation::STATE_WAITING)
            ->where(fn ($b) => ProductReservationQuery::whereExtraPaymentExpired($b, $this->waitingTime));
    }
}
