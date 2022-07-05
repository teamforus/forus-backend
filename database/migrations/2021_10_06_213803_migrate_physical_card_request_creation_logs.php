<?php

use Illuminate\Database\Migrations\Migration;
use App\Services\EventLogService\Models\EventLog;
use App\Models\PhysicalCardRequest;
use App\Models\Voucher;

/**
 * @noinspection PhpUnused
 */
class MigratePhysicalCardRequestCreationLogs extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        $logs = EventLog::whereHasMorph('loggable', PhysicalCardRequest::class)->where([
            'event' => 'created',
        ])->get();

        foreach ($logs as $log) {
            /** @var PhysicalCardRequest $physicalCardRequest */
            $physicalCardRequest = $log->loggable;

            $log->forceFill([
                'event' => $physicalCardRequest->voucher::EVENT_PHYSICAL_CARD_REQUESTED,
                'loggable_id' => $physicalCardRequest->voucher->id,
                'loggable_type' => $physicalCardRequest->voucher->getMorphClass(),
            ])->update();
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void {}
}
