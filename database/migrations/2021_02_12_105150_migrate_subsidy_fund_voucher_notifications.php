<?php

use App\Models\Notification;
use App\Models\Voucher;
use App\Services\EventLogService\Models\EventLog;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Migrations\Migration;

return new class () extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        $this->migrateVouchersCreated();
        $this->migrateVouchersAssigned();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
    }

    protected function migrateVouchersCreated(): void
    {
        $events = $this->getEvents('voucher', 'assigned');

        foreach ($events as $event) {
            if (!$event->loggable instanceof Voucher) {
                continue;
            }

            $notifications = Notification::where([
                'type' => 'App\Notifications\Identities\Voucher\IdentityVoucherAssignedNotification',
            ])->where('data->event_id', $event->id);

            $notifications->update([
                'type' => 'App\Notifications\Identities\Voucher\IdentityVoucherAssignedBudgetNotification',
                'data->key' => 'notifications_identities.identity_voucher_assigned_budget',
            ]);
        }
    }

    /**
     * @return void
     */
    protected function migrateVouchersAssigned(): void
    {
        $events = $this->getEvents('voucher', 'created_budget');

        foreach ($events as $event) {
            if (!$event->loggable instanceof Voucher) {
                continue;
            }

            $notifications = Notification::where([
                'type' => 'App\Notifications\Identities\Voucher\IdentityVoucherAddedNotification',
            ])->where('data->event_id', $event->id);

            $notifications->update([
                'type' => 'App\Notifications\Identities\Voucher\IdentityVoucherAddedBudgetNotification',
                'data->key' => 'notifications_identities.voucher_added_budget',
            ]);
        }
    }

    /**
     * @param string $loggable_type
     * @param string $event
     * @return Collection
     */
    protected function getEvents(string $loggable_type, string $event): Collection
    {
        /** @var Collection|EventLog[] $events */
        $events = EventLog::where([
            'loggable_type' => $loggable_type,
            'event' => $event,
        ])->get();

        return $events;
    }
};
