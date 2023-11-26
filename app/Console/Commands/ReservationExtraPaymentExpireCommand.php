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

    public function __construct()
    {
        parent::__construct();
        $this->waitingTime = Config::get('forus.reservations.extra_payment_waiting_time', 60);
    }

    /**
     * Execute the console command.
     *
     * @return void
     * @throws Throwable
     */
    public function handle(): void
    {
        foreach ($this->getReservationsWithExpiredExtraPaymentsQuery()->get() as $reservation) {
            try {
                $reservation->cancelByState($reservation::STATE_CANCELED_PAYMENT_EXPIRED);
            } catch (Throwable) {}
        }
    }

    /**
     * @return Collection|ProductReservation
     */
    public function getReservationsWithExpiredExtraPaymentsQuery(): Builder|ProductReservation
    {
        return ProductReservation::query()
            ->where('state', ProductReservation::STATE_WAITING)
            ->where(fn ($b) => ProductReservationQuery::whereExtraPaymentExpired($b, $this->waitingTime));
    }
}
