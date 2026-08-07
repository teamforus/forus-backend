<?php

use App\Services\EventLogService\Models\EventLog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $existingFundIds = DB::table('funds')
            ->pluck('id')
            ->mapWithKeys(fn ($fundId) => [(int) $fundId => true])
            ->all();

        EventLog::whereNotNull('data->fund_id')
            ->select(['id', 'data'])
            ->chunkById(1000, function (Collection $logs) use ($existingFundIds) {
                $now = now();

                $relations = $logs
                    ->filter(function (EventLog $log) use ($existingFundIds) {
                        $fundId = $log->data['fund_id'] ?? null;

                        return
                            filter_var($fundId, FILTER_VALIDATE_INT) !== false &&
                            isset($existingFundIds[(int) $fundId]);
                    })
                    ->map(fn (EventLog $log) => [
                        'event_log_id' => $log->id,
                        'fund_id' => (int) $log->data['fund_id'],
                        'created_at' => $now,
                        'updated_at' => $now,
                    ])
                    ->values()
                    ->all();

                if (!empty($relations)) {
                    DB::table('event_log_relations')->upsert($relations, ['event_log_id'], ['fund_id', 'updated_at']);
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {

    }
};
