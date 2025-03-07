<?php

use App\Models\Voucher;
use App\Services\EventLogService\Models\EventLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Migrations\Migration;

return new class () extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        /** @var EventLog[] $eventLogs */
        $eventLogs = EventLog::whereHasMorph('loggable', Voucher::class, function (Builder $builder) {
            $builder->whereNotNull('note');
        })->whereIn('event', Voucher::EVENTS_CREATED)->get();

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
    public function down(): void
    {
    }
};
