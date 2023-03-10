<?php

namespace App\Console\Commands;

use App\Events\BankConnections\BankConnectionExpiration;
use App\Models\BankConnection;
use App\Services\BankService\Models\Bank;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

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
        $expireAt = now()->add(
            config('forus.bng.notify.expire_time.notification.unit'),
            config('forus.bng.notify.expire_time.notification.value')
        );

        $this->getBankConnections($expireAt)
            ->each(fn(BankConnection $connection) => BankConnectionExpiration::dispatch($connection));
    }

    /**
     * @return void
     */
    private function makeAnnouncements(): void
    {
        $expireAt = now()->add(
            config('forus.bng.notify.expire_time.announcement.unit'),
            config('forus.bng.notify.expire_time.announcement.value')
        );

        $bankConnections = $this->getBankConnections($expireAt);

        foreach ($bankConnections as $bankConnection) {
            $bankConnection->announcements()->create([
                'type' => 'danger',
                'title' => trans('notifications/notifications_bank_connections.announcement.title'),
                'description' => trans('notifications/notifications_bank_connections.announcement.description'),
                'scope' => 'sponsor',
                'active' => true,
            ]);
        }
    }

    /**
     * @param Carbon $expireAt
     * @return array|EloquentCollection|Collection|BankConnection[]
     */
    private function getBankConnections(Carbon $expireAt): array|EloquentCollection|Collection
    {
        return BankConnection::whereHas('bank', function(Builder $builder) {
            $builder->where('key', Bank::BANK_BNG);
        })
            ->whereState(BankConnection::STATE_ACTIVE)
            ->whereDate('session_expire_at', $expireAt)
            ->get();
    }
}
