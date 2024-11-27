<?php

namespace App\Console\Commands;

use App\Events\Funds\FundBalanceLowEvent;
use App\Models\Fund;
use App\Scopes\Builders\FundQuery;
use Illuminate\Console\Command;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Throwable;

class NotifyAboutReachedNotificationFundAmount extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'forus.fund:check-amount';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check if budget left reached notification amount';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void
    {
        try {
            foreach ($this->getLowBalanceFunds() as $fund) {
                $transactionCosts = $fund->getTransactionCosts();

                if ($fund->budget_left - $transactionCosts <= $fund->notification_amount) {
                    FundBalanceLowEvent::dispatch($fund);
                }
            }
        } catch (Throwable) {}
    }

    /**
     * @param int $notificationInterval
     * @return Fund[]|Builder[]|Collection
     */
    public function getLowBalanceFunds(int $notificationInterval = 7): Collection|Arrayable
    {
        $fundsQuery = Fund::where(function (Builder $query) {
            FundQuery::whereActiveFilter($query);
            FundQuery::whereIsInternal($query);
            FundQuery::whereIsConfiguredByForus($query);
        })->where(static function (Builder $query) use ($notificationInterval) {
            $query->whereNull('notified_at');
            $query->orWhereDate('notified_at', '<=', now()->subDays($notificationInterval)->startOfDay());
        });

        $fundsQuery->whereNotNull('notification_amount');
        $fundsQuery->where('notification_amount', '>', 0);

        return $fundsQuery->get();
    }
}
