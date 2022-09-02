<?php

use App\Services\EventLogService\Models\EventLog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Eloquent\Collection;
use App\Models\Notification;
use App\Models\Voucher;

return new class extends Migration
{
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

    protected function migrateVouchersCreated()
    {
        $events = $this->getEvents('voucher', 'assigned');

        foreach ($events as $event) {
            if (!$event->loggable instanceof Voucher) {
                continue;
            }

            $voucher = $event->loggable;
            $notifications = Notification::where([
                'type' => 'App\Notifications\Identities\Voucher\IdentityVoucherAssignedNotification'
            ])->where('data->event_id', $event->id);

            if ($voucher->fund->isTypeSubsidy()) {
                $notifications->update([
                    'type' => 'App\Notifications\Identities\Voucher\IdentityVoucherAssignedSubsidyNotification',
                    'data->key' => 'notifications_identities.identity_voucher_assigned_subsidy',
                ]);
            } else {
                $notifications->update([
                    'type' => 'App\Notifications\Identities\Voucher\IdentityVoucherAssignedBudgetNotification',
                    'data->key' => 'notifications_identities.identity_voucher_assigned_budget',
                ]);
            }
        }
    }

    protected function migrateVouchersAssigned()
    {
        $events = $this->getEvents('voucher', 'created_budget');

        foreach ($events as $event) {
            if (!$event->loggable instanceof Voucher) {
                continue;
            }

            $voucher = $event->loggable;
            $notifications = Notification::where([
                'type' => 'App\Notifications\Identities\Voucher\IdentityVoucherAddedNotification'
            ])->where('data->event_id', $event->id);

            if ($voucher->fund->isTypeSubsidy()) {
                $notifications->update([
                    'type' => 'App\Notifications\Identities\Voucher\IdentityVoucherAddedSubsidyNotification',
                    'data->key' => 'notifications_identities.voucher_added_subsidy',
                ]);
            } else {
                $notifications->update([
                    'type' => 'App\Notifications\Identities\Voucher\IdentityVoucherAddedBudgetNotification',
                    'data->key' => 'notifications_identities.voucher_added_budget',
                ]);
            }
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

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {}
};
