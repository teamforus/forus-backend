<?php

namespace App\Console\Commands\IConnect\Traits;

trait BaseIConnect
{
    public const string PREVALIDATION_REQUEST_TYPE = 'prevalidation_request';
    public const string FUND_REQUEST_TYPE = 'fund_request';

    /**
     * @var string
     */
    protected string $type;

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

            if ($this->type === static::PREVALIDATION_REQUEST_TYPE) {
                $this->table([
                    'Prevalidation request ID', 'Prevalidation ID', 'Added', 'Updated', 'Deleted', 'Failed',
                ], $this->statsDetails);
            }

            if ($this->type === static::FUND_REQUEST_TYPE) {
                $this->table([
                    'Fund request ID', 'Added', 'Updated', 'Deleted', 'Failed',
                ], $this->statsDetails);
            }
        }

        $this->printSeparator();
    }

    /**
     * @param int $requestId
     * @param int|null $prevalidationId
     * @param string $added
     * @param string $updated
     * @param string $deleted
     * @param string $failed
     * @return void
     */
    protected function addStatistic(
        int $requestId,
        ?int $prevalidationId = null,
        string $added = '',
        string $updated = '',
        string $deleted = '',
        string $failed = ''
    ): void {
        $this->statsDetails[] = [
            'request_id' => $requestId,
            ...$this->type === static::PREVALIDATION_REQUEST_TYPE ? ['prevalidation_id' => $prevalidationId ?? ''] : [],
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
    protected function mapRequestRecords(array $records): array
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
    protected function diffArrayToString(array $array, bool $isUpdateArray = false): string
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
    protected function diffArrayToTableValues(array $array, bool $isUpdateArray = false): array
    {
        return $isUpdateArray
            ? array_map(fn ($value, $key) => [$key, $value['old'], $value['new']], $array, array_keys($array))
            : array_map(fn ($value, $key) => [$key, $value], $array, array_keys($array));
    }

    /**
     * @param array $array
     * @return array
     */
    protected function normalizeData(array $array): array
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
    protected function resetStats(): void
    {
        $this->stats = array_map(fn () => 0, $this->stats);
        $this->statsDetails = [];
    }
}
