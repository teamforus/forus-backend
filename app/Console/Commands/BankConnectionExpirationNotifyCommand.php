<?php

namespace App\Console\Commands;

use App\Events\BankConnections\BankConnectionExpiring;
use App\Models\BankConnection;
use App\Services\BankService\Models\Bank;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Config;

class BankConnectionExpirationNotifyCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bank:notify-connection-expiration';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Notify about bank connection context expiration';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void
    {
        $this->makeNotifications();
        $this->makeAnnouncements();
    }

    /**
     * @return void
     */
    private function makeNotifications(): void
    {
        $connections = $this->getExpiringBankConnectionsQuery(now()->add(
            Config::get('forus.bng.notify.expire_time.notification.unit'),
            Config::get('forus.bng.notify.expire_time.notification.value'),
        ))->whereDoesntHave('logs', fn (Builder $b) => $b->where([
            'event' => BankConnection::EVENT_EXPIRING,
        ]))->get();

        $connections->each(function (BankConnection $connection) {
            BankConnectionExpiring::dispatch($connection);
        });
    }

    /**
     * @return void
     */
    private function makeAnnouncements(): void
    {
        $connections = $this->getExpiringBankConnectionsQuery(now()->add(
            Config::get('forus.bng.notify.expire_time.announcement.unit'),
            Config::get('forus.bng.notify.expire_time.announcement.value'),
        ))->whereDoesntHave('announcements', fn (Builder $b) => $b->where([
            'key' => 'bank_connection.expiring',
        ]))->get();

        $connections->each(fn (BankConnection $connection) => $connection->announcements()->updateOrCreate([
            'key' => 'bank_connection.expiring',
        ], [
            'type' => 'danger',
            'dismissible' => false,
            'scope' => 'sponsor',
            'active' => true,
            'title' => trans('notifications/notifications_bank_connections.announcement.title', [
                'expire_at_locale' => format_date_locale($connection->expire_at),
            ]),
            'description' => trans('notifications/notifications_bank_connections.announcement.description', [
                'expire_at_locale' => format_date_locale($connection->expire_at),
            ]),
        ]));
    }

    /**
     * @param Carbon $expireAt
     * @return Builder|BankConnection
     */
    private function getExpiringBankConnectionsQuery(Carbon $expireAt): Builder|BankConnection
    {
        return BankConnection::query()
            ->whereRelation('bank', 'key', Bank::BANK_BNG)
            ->whereState(BankConnection::STATE_ACTIVE)
            ->whereDate('expire_at', '<=', $expireAt->startOfDay());
    }
}