<?php

namespace App\Console\Commands;

use App\Models\Prevalidation;
use App\Models\PrevalidationRecord;
use App\Models\PrevalidationRequest;
use App\Services\IConnectApiService\IConnectPrefill;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
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
    protected $description = 'Process prevalidations with new records from IConnect';

    /**
     * @var bool
     */
    protected bool $acceptAll = false;

    /**
     * @var array
     */
    protected array $stats = [
        'updated' => 0,
        'skipped' => 0,
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
        $hours = $this->ask('Select prevalidations older than number of hours:', 5);

        $prevalidations = Prevalidation::query()
            ->with(['prevalidation_request', 'records.record_type'])
            ->where('state', $state)
            ->whereHas('prevalidation_request', function (Builder $builder) use ($hours) {
                $builder->where('state', PrevalidationRequest::STATE_SUCCESS);
                $builder->where('fetched_date', '<=', now()->subHours($hours));
            })
            ->get();

        if ($prevalidations->isEmpty()) {
            $this->printText('No prevalidations found!');
            $this->printSeparator();
            $this->askAction();

            return;
        }

        $proceed = $this->ask("{$prevalidations->count()} prevalidations found, proceed?", 'yes');

        if ($proceed === 'yes') {
            $this->processPrevalidations($prevalidations, $state);
            $this->printStats();
        }
    }

    /**
     * @param Collection $prevalidations
     * @param string $state
     * @throws Throwable
     * @return void
     */
    protected function processPrevalidations(Collection $prevalidations, string $state): void
    {
        $this->printText('Progress:');
        $bar = $this->output->createProgressBar($prevalidations->count());
        $bar->start();

        $prevalidations->each(function (Prevalidation $prevalidation) use ($bar, $state) {
            if ($state === $prevalidation::STATE_PENDING) {
                $this->updatePendingPrevalidation($prevalidation, $bar);
            }

            if ($state === $prevalidation::STATE_USED) {
                $this->updateUsedPrevalidation($prevalidation, $bar);
            }
        });

        $bar->finish();
        $this->newLine();
        $this->printSeparator();
    }

    /**
     * @param Prevalidation $prevalidation
     * @param ProgressBar $bar
     * @return void
     */
    protected function updatePendingPrevalidation(Prevalidation $prevalidation, ProgressBar $bar): void
    {
        $existingData = $this->getExistingRecordData($prevalidation);
        $newData = $this->getNewRecordData($prevalidation);

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
            $this->stats['skipped']++;

            return;
        }

        $this->newLine(2);

        // print difference between data
        if (count($added)) {
            $this->printText("Found new records for prevalidation #$prevalidation->id:");
            $this->table(['Record', 'Value'], $this->diffArrayToTableValues($added));
            $this->newLine();
        }

        if (count($updated)) {
            $this->printText("Next records will be updated for prevalidation #$prevalidation->id:");
            $this->table(['Record', 'Old value', 'New value'], $this->diffArrayToTableValues($updated, true));
            $this->newLine();
        }

        if (count($deleted)) {
            $this->printText("Next records will be deleted for prevalidation #$prevalidation->id:");
            $this->table(['Record', 'Value'], $this->diffArrayToTableValues($deleted));
            $this->newLine();
        }

        $this->newLine();

        $accepted = $this->acceptChangesPendingPrevalidation($prevalidation, $newData);

        if ($accepted) {
            // add to stats
            $addedText = $this->diffArrayToString($added);
            $deletedText = $this->diffArrayToString($deleted);
            $updatedText = $this->diffArrayToString($updated, true);

            $this->addStatistic($prevalidation->id, added: $addedText, updated: $updatedText, deleted: $deletedText);
        }

        $this->newLine();
        $bar->advance();
    }

    /**
     * @param Prevalidation $prevalidation
     * @param ProgressBar $bar
     * @return void
     */
    protected function updateUsedPrevalidation(Prevalidation $prevalidation, ProgressBar $bar): void
    {
        $existingData = $this->getExistingRecordData($prevalidation);
        $newData = $this->getNewRecordData($prevalidation);

        if (!$newData) {
            $bar->advance();
            $this->stats['failed']++;

            return;
        }

        $added = array_diff_key($newData, $existingData);

        if (!count($added)) {
            $bar->advance();
            $this->stats['skipped']++;

            return;
        }

        $this->newLine(2);

        $this->printText("Found new records for prevalidation #$prevalidation->id:");
        $this->table(['Record', 'Value'], $this->diffArrayToTableValues($added));

        $this->newLine();

        $accepted = $this->acceptChangesUsedPrevalidation($prevalidation, $added);

        if ($accepted) {
            $this->addStatistic($prevalidation->id, added: $this->diffArrayToString($added));
        }

        $this->newLine();
        $bar->advance();
    }

    /**
     * @param Prevalidation $prevalidation
     * @return array|null
     */
    protected function getNewRecordData(Prevalidation $prevalidation): ?array
    {
        $request = $prevalidation->prevalidation_request;

        $fundPrefills = IConnectPrefill::getBsnApiPrefills($request->fund, $request->bsn, withResponseData: true);

        if (is_array($fundPrefills['error'])) {
            $this->newLine();
            $this->printText("Prevalidation #$prevalidation->id:");
            $this->printText($this->red('Fetch from BRP failed: ' . Arr::get($fundPrefills, 'error.key')));
            $this->addStatistic($prevalidation->id, failed: Arr::get($fundPrefills, 'error.key'));

            return null;
        }

        $newData = $request->prepareRecords($fundPrefills);

        if (!$request->recordsIsValid($request->fund, $newData)) {
            $this->newLine();
            $this->printText("Prevalidation #$prevalidation->id:");
            $this->printText($this->red('Fetch from BRP failed: Invalid records for the fund'));
            $this->addStatistic($prevalidation->id, failed: 'Invalid records for the fund');

            return null;
        }

        $prevalidation->prevalidation_request->update(['fetched_date' => now()]);

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
     * @param Prevalidation $prevalidation
     * @param array $newData
     * @return bool
     */
    protected function acceptChangesPendingPrevalidation(Prevalidation $prevalidation, array $newData): bool
    {
        if ($this->acceptAll) {
            $accept = 1;
        } else {
            $this->printList([
                '### Accept changes:',
                '[1] Accept.',
                '[2] Accept all.',
                '[3] Skip.',
            ]);

            $accept = $this->ask('Would you like to update the prevalidation?', 1);
        }

        switch ($accept) {
            case 1:
                $prevalidation->updatePrevalidationRecords($newData);
                $this->printText("Prevalidation #$prevalidation->id updated.");
                $this->stats['updated']++;

                return true;
            case 2:
                $this->acceptAll = true;
                $prevalidation->updatePrevalidationRecords($newData);
                $this->printText("Prevalidation #$prevalidation->id updated.");
                $this->stats['updated']++;

                return true;
            case 3:
                $this->printText("Prevalidation #$prevalidation->id skipped.");
                $this->stats['skipped']++;

                return false;
            default:
                $this->printText("Invalid input!\nPlease try again:\n");

                return $this->acceptChangesPendingPrevalidation($prevalidation, $newData);
        }
    }

    /**
     * @param Prevalidation $prevalidation
     * @param array $addedData
     * @return bool
     */
    protected function acceptChangesUsedPrevalidation(Prevalidation $prevalidation, array $addedData): bool
    {
        if ($this->acceptAll) {
            $accept = 1;
        } else {
            $this->printList([
                '### Accept changes:',
                '[1] Accept.',
                '[2] Accept all.',
                '[3] Skip.',
            ]);

            $accept = $this->ask('Would you like to update the prevalidation?', 1);
        }

        switch ($accept) {
            case 1:
                $prevalidation->addPrevalidationRecords($addedData);
                $this->printText("Prevalidation #$prevalidation->id updated.");
                $this->stats['updated']++;

                return true;
            case 2:
                $this->acceptAll = true;
                $prevalidation->addPrevalidationRecords($addedData);
                $this->printText("Prevalidation #$prevalidation->id updated.");
                $this->stats['updated']++;

                return true;
            case 3:
                $this->printText("Prevalidation #$prevalidation->id skipped.");
                $this->stats['skipped']++;

                return false;
            default:
                $this->printText("Invalid input!\nPlease try again:\n");

                return $this->acceptChangesUsedPrevalidation($prevalidation, $addedData);
        }
    }

    /**
     * @return void
     */
    protected function printStats(): void
    {
        $this->printHeader('Stats:');

        $this->printListWithValues([
            'Updated:' => $this->stats['updated'],
            'Skipped:' => $this->stats['skipped'],
            'Failed:' => $this->stats['failed'],
        ]);

        if (count($this->statsDetails)) {
            $this->newLine();
            $this->printHeader('Stats details:');
            $this->table(['ID', 'Added', 'Updated', 'Deleted', 'Failed'], $this->statsDetails);
        }

        $this->printSeparator();
    }

    /**
     * @param int $id
     * @param string $added
     * @param string $updated
     * @param string $deleted
     * @param string $failed
     * @return void
     */
    private function addStatistic(
        int $id,
        string $added = '',
        string $updated = '',
        string $deleted = '',
        string $failed = ''
    ): void {
        $this->statsDetails[] = [
            'id' => $id,
            'added' => $added,
            'updated' => $updated,
            'deleted' => $deleted,
            'failed' => $failed,
        ];
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
}
