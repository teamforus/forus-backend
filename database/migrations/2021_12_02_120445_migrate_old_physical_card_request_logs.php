<?php

use App\Services\EventLogService\EventLogService;
use Illuminate\Database\Migrations\Migration;
use App\Models\Notification;
use App\Models\Implementation;

/**
 * @noinspection PhpUnused
 */
class MigrateOldPhysicalCardRequestLogs extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        $eventLogService = resolve(EventLogService::class);
        $notifications = Notification::where([
            'type' => 'App\Notifications\Identities\PhysicalCardRequest\PhysicalCardRequestCreatedNotification',
            'data->key' => 'notifications_identities.physical_card_request_created',
        ])->get();

        foreach ($notifications as $notification) {
            $this->migrateNotification($eventLogService, $notification);
        }
    }

    /**
     * @param EventLogService $eventLogService
     * @param Notification $notification
     */
    protected function migrateNotification(
        EventLogService $eventLogService,
        Notification $notification
    ): void {
        $notification->update([
            'type' => 'App\Notifications\Identities\Voucher\IdentityVoucherPhysicalCardRequestedNotification',
            'data->key' => 'notifications_identities.voucher_physical_card_requested',
        ]);

        $eventLog = $notification->findEventLog();
        $implementation = Implementation::byKey($eventLog->data['implementation_key'] ?? null);

        if ($eventLog && $implementation) {
            $implementationData = $eventLogService->modelToMeta('implementation', $implementation);

            $eventLog->forceFill(collect($implementationData)->mapWithKeys(function($value, $key) {
                return ["data->$key" => $value];
            })->toArray())->update();
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Notification::where([
            'type' => 'App\Notifications\Identities\Voucher\IdentityVoucherPhysicalCardRequestedNotification',
            'data->key' => 'notifications_identities.voucher_physical_card_requested',
        ])->update([
            'type' => 'App\Notifications\Identities\PhysicalCardRequest\PhysicalCardRequestCreatedNotification',
            'data->key' => 'notifications_identities.physical_card_request_created',
        ]);
    }
}
