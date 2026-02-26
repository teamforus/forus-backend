<?php

namespace App\Console\Commands;

use App\Events\PrevalidationRequests\PrevalidationRequestRecordsUpdatedEvent;
use App\Models\Prevalidation;
use App\Models\PrevalidationRecord;
use App\Models\PrevalidationRequest;
use App\Services\IConnectApiService\IConnectPrefill;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use Symfony\Component\Console\Helper\ProgressBar;
use Throwable;

class IConnectCliCommand extends BaseCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'iconnect:cli';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update prevalidation request records from IConnect and sync prevalidations';

    /**
     * @var bool
     */
    protected bool $acceptAll = false;

    /**
     * @var array
     */
    protected array $stats = [
        'updated' => 0,
        'skipped_no_diff' => 0,
        'skipped_manual' => 0,
        'skipped_invalid_link' => 0,
        'skipped_records_mismatch' => 0,
        'failed' => 0,
    ];

    /**
     * @var array
     */
    protected array $statsDetails = [];

    /**
     * @throws Throwable
     * @return void
     */
    public function handle(): void
    {
        $this->askAction();
    }

    /**
     * @return array
     */
    protected function askActionList(): array
    {
        return [
            '### Prevalidation:',
            '[1] Update pending prevalidations.',
            '[2] Update used prevalidations.',
            '[3] Exit',
        ];
    }

    /**
     * @throws Throwable
     * @return void
     */
    protected function askAction(): void
    {
        $this->resetStats();
        $this->printHeader('Select next action:');
        $this->printList($this->askActionList());
        $action = $this->ask('Please select next step:', 1);

        switch ($action) {
            case 1: $this->updatePrevalidations(Prevalidation::STATE_PENDING);
                break;
            case 2: $this->updatePrevalidations(Prevalidation::STATE_USED);
                break;
            case 3: $this->exit();
                // no break
            default: $this->printText("Invalid input!\nPlease try again:\n");
        }

        $this->askAction();
    }

    /**
     * @param string $state
     * @throws Throwable
     * @return void
     */
    protected function updatePrevalidations(string $state): void
    {
        $hours = $this->ask('Select prevalidation requests older than number of hours:', 5);

        $requests = PrevalidationRequest::query()
            ->with(['prevalidation.records.record_type', 'records', 'fund'])
            ->where('state', PrevalidationRequest::STATE_SUCCESS)
            ->where('fetched_date', '<=', now()->subHours($hours))
            ->whereHas('prevalidation', fn (Builder $builder) => $builder->where('state', $state))
            ->get();

        if ($requests->isEmpty()) {
            $this->printText('No prevalidation requests found!');
            $this->printSeparator();
            $this->askAction();

            return;
        }

        $proceed = $this->ask("{$requests->count()} prevalidation requests found, proceed?", 'yes');

        if ($proceed === 'yes') {
            $this->processPrevalidations($requests, $state);
            $this->printStats();
        }
    }

    /**
     * @param Collection $requests
     * @param string $state
     * @throws Throwable
     * @return void
     */
    protected function processPrevalidations(Collection $requests, string $state): void
    {
        $this->printText('Progress:');
        $bar = $this->output->createProgressBar($requests->count());
        $bar->start();

        $requests->each(function (PrevalidationRequest $request) use ($bar, $state) {
            $prevalidations = Prevalidation::where('prevalidation_request_id', $request->id)->get();
            $prevalidations_count = $prevalidations->count();

            if ($prevalidations_count !== 1) {
                $this->newLine();
                $this->printText("Request #$request->id has $prevalidations_count linked prevalidations, skipping.");
                $this->stats['skipped_invalid_link']++;
                $this->addStatistic(prevalidationRequestId: $request->id, failed: 'invalid_link');
                $bar->advance();

                return;
            }

            $prevalidation = $prevalidations->first();

            if ($this->recordsMismatch($request, $prevalidation)) {
                $bar->advance();

                return;
            }

            if ($state === $prevalidation::STATE_PENDING) {
                $this->updatePendingPrevalidation($request, $prevalidation, $bar);
            }

            if ($state === $prevalidation::STATE_USED) {
                $this->updateUsedPrevalidation($request, $prevalidation, $bar);
            }
        });

        $bar->finish();
        $this->newLine();
        $this->printSeparator();
    }

    /**
     * @param PrevalidationRequest $request
     * @param Prevalidation $prevalidation
     * @param ProgressBar $bar
     * @return void
     */
    protected function updatePendingPrevalidation(
        PrevalidationRequest $request,
        Prevalidation $prevalidation,
        ProgressBar $bar
    ): void {
        $existingData = $this->getExistingRecordData($prevalidation);
        $newData = $this->getNewRecordData($request, $prevalidation);

        if (!$newData) {
            $bar->advance();
            $this->stats['failed']++;

            return;
        }

        // compare records
        $added = array_diff_key($newData, $existingData);
        $deleted = array_diff_key($existingData, $newData);
        $updated = [];

        foreach ($newData as $key => $value) {
            if (array_key_exists($key, $existingData) && $existingData[$key] != $value) {
                $updated[$key] = [
                    'old' => $existingData[$key],
                    'new' => $value,
                ];
            }
        }

        if (!count($added) && !count($deleted) && !count($updated)) {
            $bar->advance();
            $this->stats['skipped_no_diff']++;

            return;
        }

        $this->newLine(2);

        // print difference between data
        if (count($added)) {
            $this->printText("Found new records for request #$request->id, prevalidation #$prevalidation->id:");
            $this->table(['Record', 'Value'], $this->diffArrayToTableValues($added));
            $this->newLine();
        }

        if (count($updated)) {
            $this->printText("Records to update for request #$request->id, prevalidation #$prevalidation->id:");
            $this->table(['Record', 'Old value', 'New value'], $this->diffArrayToTableValues($updated, true));
            $this->newLine();
        }

        if (count($deleted)) {
            $this->printText("Records to delete for request #$request->id, prevalidation #$prevalidation->id:");
            $this->table(['Record', 'Value'], $this->diffArrayToTableValues($deleted));
            $this->newLine();
        }

        $this->newLine();

        if ($this->acceptChangesPendingPrevalidation($request, $prevalidation, $newData)) {
            Event::dispatch(new PrevalidationRequestRecordsUpdatedEvent(
                $request,
                $prevalidation->id,
                $prevalidation->state,
                'replace',
                $added,
                $updated,
                $deleted,
            ));

            // add to stats
            $addedText = $this->diffArrayToString($added);
            $deletedText = $this->diffArrayToString($deleted);
            $updatedText = $this->diffArrayToString($updated, true);

            $this->addStatistic(
                prevalidationRequestId: $request->id,
                prevalidationId: $prevalidation->id,
                added: $addedText,
                updated: $updatedText,
                deleted: $deletedText
            );
        }

        $this->newLine();
        $bar->advance();
    }

    /**
     * @param PrevalidationRequest $request
     * @param Prevalidation $prevalidation
     * @param ProgressBar $bar
     * @return void
     */
    protected function updateUsedPrevalidation(
        PrevalidationRequest $request,
        Prevalidation $prevalidation,
        ProgressBar $bar
    ): void {
        $existingData = $this->getExistingRecordData($prevalidation);
        $newData = $this->getNewRecordData($request, $prevalidation);

        if (!$newData) {
            $bar->advance();
            $this->stats['failed']++;

            return;
        }

        $added = array_diff_key($newData, $existingData);

        if (!count($added)) {
            $bar->advance();
            $this->stats['skipped_no_diff']++;

            return;
        }

        $this->newLine(2);

        $this->printText("Found new records for request #$request->id, prevalidation #$prevalidation->id:");
        $this->table(['Record', 'Value'], $this->diffArrayToTableValues($added));
        $this->newLine();

        $accepted = $this->acceptChangesUsedPrevalidation($request, $prevalidation, $added);

        if ($accepted) {
            Event::dispatch(new PrevalidationRequestRecordsUpdatedEvent(
                $request,
                $prevalidation->id,
                $prevalidation->state,
                'add',
                $added,
                [],
                [],
            ));

            $this->addStatistic($request->id, $prevalidation->id, added: $this->diffArrayToString($added));
        }

        $this->newLine();
        $bar->advance();
    }

    /**
     * @param PrevalidationRequest $request
     * @param Prevalidation $prevalidation
     * @return array|null
     */
    protected function getNewRecordData(PrevalidationRequest $request, Prevalidation $prevalidation): ?array
    {
        $fundPrefills = IConnectPrefill::getBsnApiPrefills($request->fund, $request->bsn, withResponseData: true);

        if (is_array($fundPrefills['error'])) {
            $this->newLine();
            $this->printText("Prevalidation request #$request->id, prevalidation #$prevalidation->id:");
            $this->printText($this->red('Fetch from BRP failed: ' . Arr::get($fundPrefills, 'error.key')));
            $this->addStatistic($request->id, $prevalidation->id, failed: Arr::get($fundPrefills, 'error.key'));

            return null;
        }

        $newData = $request->prepareRecords($fundPrefills);

        if (!$request->recordsIsValid($request->fund, $newData)) {
            $this->newLine();
            $this->printText("Prevalidation request #$request->id, prevalidation #$prevalidation->id:");
            $this->printText($this->red('Fetch from BRP failed: Invalid records for the fund'));
            $this->addStatistic($request->id, $prevalidation->id, failed: 'Invalid records for the fund');

            return null;
        }

        $request->update(['fetched_date' => now()]);

        return $this->normalizeData($newData);
    }

    /**
     * @param Prevalidation $prevalidation
     * @return array
     */
    protected function getExistingRecordData(Prevalidation $prevalidation): array
    {
        return $this->normalizeData($prevalidation->records->mapWithKeys(fn (PrevalidationRecord $record) => [
            $record->record_type->key => $record->value,
        ])->toArray());
    }

    /**
     * @param PrevalidationRequest $request
     * @return array
     */
    protected function getRequestRecordData(PrevalidationRequest $request): array
    {
        return $this->normalizeData($request->records->pluck('value', 'record_type_key')->toArray());
    }

    /**
     * @param PrevalidationRequest $request
     * @param Prevalidation $prevalidation
     * @return bool
     */
    protected function recordsMismatch(PrevalidationRequest $request, Prevalidation $prevalidation): bool
    {
        $requestData = $this->getRequestRecordData($request);
        $existingData = $this->getExistingRecordData($prevalidation);

        foreach ($existingData as $key => $value) {
            if (!array_key_exists($key, $requestData) || $requestData[$key] != $value) {
                $this->newLine();
                $this->printText(
                    "Request #$request->id, prevalidation #$prevalidation->id records mismatch, skipping."
                );
                $this->stats['skipped_records_mismatch']++;
                $this->addStatistic($request->id, $prevalidation->id, failed: 'records_mismatch');

                return true;
            }
        }

        return false;
    }

    /**
     * @param PrevalidationRequest $request
     * @param Prevalidation $prevalidation
     * @param array $newData
     * @return bool
     */
    protected function acceptChangesPendingPrevalidation(
        PrevalidationRequest $request,
        Prevalidation $prevalidation,
        array $newData
    ): bool {
        if ($this->acceptAll) {
            $accept = 1;
        } else {
            $this->printList([
                '### Accept changes:',
                '[1] Accept.',
                '[2] Accept all.',
                '[3] Skip.',
            ]);

            $accept = $this->ask('Would you like to update the prevalidation request?', 1);
        }

        switch ($accept) {
            case 1:
                $prevalidation->updatePrevalidationRecords($newData);
                $this->updatePrevalidationRequestRecords($request, $newData);
                $this->printText("Prevalidation request #$request->id, prevalidation #$prevalidation->id updated.");
                $this->stats['updated']++;

                return true;
            case 2:
                $this->acceptAll = true;
                $prevalidation->updatePrevalidationRecords($newData);
                $this->updatePrevalidationRequestRecords($request, $newData);
                $this->printText("Prevalidation request #$request->id, prevalidation #$prevalidation->id updated.");
                $this->stats['updated']++;

                return true;
            case 3:
                $this->printText("Prevalidation request #$request->id, prevalidation #$prevalidation->id skipped.");
                $this->stats['skipped_manual']++;

                return false;
            default:
                $this->printText("Invalid input!\nPlease try again:\n");

                return $this->acceptChangesPendingPrevalidation($request, $prevalidation, $newData);
        }
    }

    /**
     * @param PrevalidationRequest $request
     * @param Prevalidation $prevalidation
     * @param array $addedData
     * @return bool
     */
    protected function acceptChangesUsedPrevalidation(
        PrevalidationRequest $request,
        Prevalidation $prevalidation,
        array $addedData
    ): bool {
        if ($this->acceptAll) {
            $accept = 1;
        } else {
            $this->printList([
                '### Accept changes:',
                '[1] Accept.',
                '[2] Accept all.',
                '[3] Skip.',
            ]);

            $accept = $this->ask('Would you like to update the prevalidation request?', 1);
        }

        switch ($accept) {
            case 1:
                $prevalidation->addPrevalidationRecords($addedData);
                $this->addPrevalidationRequestRecords($request, $addedData);
                $this->printText("Prevalidation request #$request->id, prevalidation #$prevalidation->id updated.");
                $this->stats['updated']++;

                return true;
            case 2:
                $this->acceptAll = true;
                $prevalidation->addPrevalidationRecords($addedData);
                $this->addPrevalidationRequestRecords($request, $addedData);
                $this->printText("Prevalidation request #$request->id, prevalidation #$prevalidation->id updated.");
                $this->stats['updated']++;

                return true;
            case 3:
                $this->printText("Prevalidation request #$request->id, prevalidation #$prevalidation->id skipped.");
                $this->stats['skipped_manual']++;

                return false;
            default:
                $this->printText("Invalid input!\nPlease try again:\n");

                return $this->acceptChangesUsedPrevalidation($request, $prevalidation, $addedData);
        }
    }

    /**
     * @param PrevalidationRequest $request
     * @param array $records
     * @return void
     */
    protected function updatePrevalidationRequestRecords(PrevalidationRequest $request, array $records): void
    {
        $request->records()->delete();
        $request->records()->createMany($this->mapRequestRecords($records));
    }

    /**
     * @param PrevalidationRequest $request
     * @param array $records
     * @return void
     */
    protected function addPrevalidationRequestRecords(PrevalidationRequest $request, array $records): void
    {
        $request->records()->createMany($this->mapRequestRecords($records));
    }

    /**
     * @return void
     */
    protected function printStats(): void
    {
        $this->printHeader('Stats:');

        $this->printListWithValues([
            'Updated:' => $this->stats['updated'],
            'Skipped (no changes):' => $this->stats['skipped_no_diff'],
            'Skipped (manual):' => $this->stats['skipped_manual'],
            'Skipped (invalid link):' => $this->stats['skipped_invalid_link'],
            'Skipped (records mismatch):' => $this->stats['skipped_records_mismatch'],
            'Failed:' => $this->stats['failed'],
        ]);

        if (count($this->statsDetails)) {
            $this->newLine();
            $this->printHeader('Stats details:');
            $this->table([
                'Prevalidation request ID', 'Prevalidation ID', 'Added', 'Updated', 'Deleted', 'Failed',
            ], $this->statsDetails);
        }

        $this->printSeparator();
    }

    /**
     * @param int $prevalidationRequestId
     * @param int|null $prevalidationId
     * @param string $added
     * @param string $updated
     * @param string $deleted
     * @param string $failed
     * @return void
     */
    private function addStatistic(
        int $prevalidationRequestId,
        ?int $prevalidationId = null,
        string $added = '',
        string $updated = '',
        string $deleted = '',
        string $failed = ''
    ): void {
        $this->statsDetails[] = [
            'prevalidation_request_id' => $prevalidationRequestId,
            'prevalidation_id' => $prevalidationId ?? '',
            'added' => $added,
            'updated' => $updated,
            'deleted' => $deleted,
            'failed' => $failed,
        ];
    }

    /**
     * @param array $records
     * @return array
     */
    private function mapRequestRecords(array $records): array
    {
        return array_map(fn ($value, $key) => [
            'record_type_key' => $key,
            'value' => is_null($value) ? '' : $value,
        ], $records, array_keys($records));
    }

    /**
     * @param array $array
     * @param bool $isUpdateArray
     * @return string
     */
    private function diffArrayToString(array $array, bool $isUpdateArray = false): string
    {
        $arr = $isUpdateArray
            ? array_map(fn ($value, $key) => "$key: {$value['old']} => {$value['new']}", $array, array_keys($array))
            : array_map(fn ($value, $key) => "$key: $value", $array, array_keys($array));

        return implode("\n", $arr);
    }

    /**
     * @param array $array
     * @param bool $isUpdateArray
     * @return array
     */
    private function diffArrayToTableValues(array $array, bool $isUpdateArray = false): array
    {
        return $isUpdateArray
            ? array_map(fn ($value, $key) => [$key, $value['old'], $value['new']], $array, array_keys($array))
            : array_map(fn ($value, $key) => [$key, $value], $array, array_keys($array));
    }

    /**
     * @param array $array
     * @return array
     */
    private function normalizeData(array $array): array
    {
        return collect($array)->map(function ($value) {
            if (is_numeric($value)) {
                return (int) $value;
            }

            return $value;
        })->toArray();
    }

    /**
     * @return void
     */
    private function resetStats(): void
    {
        $this->stats = array_map(fn () => 0, $this->stats);
        $this->statsDetails = [];
    }
}
