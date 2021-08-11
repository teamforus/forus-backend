<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Voucher;
use App\Services\EventLogService\Models\EventLog;

/**
 * @noinspection PhpUnused
 */
class AddVoucherNoteToCreationLogs extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        /** @var EventLog[] $eventLogs */
        $eventLogs = EventLog::whereHasMorph('loggable', Voucher::class, function(Builder $builder) {
            $builder->whereNotNull('note');
        })->whereIn('event', [
            Voucher::EVENT_CREATED_BUDGET,
            Voucher::EVENT_CREATED_PRODUCT,
        ])->get();

        foreach ($eventLogs as $eventLog) {
            if ($eventLog->loggable instanceof Voucher) {
                $eventLog->forceFill([
                    'data->note' => $eventLog->loggable->note,
                ])->save();
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void {}
}
