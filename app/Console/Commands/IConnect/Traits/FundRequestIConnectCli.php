<?php

namespace App\Console\Commands\IConnect\Traits;

use App\Events\FundRequests\FundRequestRecordsUpdatedEvent;
use App\Models\FundRequest;
use App\Models\FundRequestRecord;
use App\Services\IConnectApiService\IConnectPrefill;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use Symfony\Component\Console\Helper\ProgressBar;
use Throwable;

trait FundRequestIConnectCli
{
    use BaseIConnect;

    /**
     * @throws Throwable
     * @return void
     */
    protected function updateFundRequests(): void
    {
        $this->type = self::FUND_REQUEST_TYPE;
        $fund_id = $this->ask('Select fund id:');

        if (!$fund_id) {
            $this->updateFundRequests();

            return;
        }

        $requests = FundRequest::query()
            ->with(['records', 'fund'])
            ->whereRelation('fund.fund_config', 'allow_fund_request_prefill', true)
            ->whereRelation('fund.organization', 'bsn_enabled', true)
            ->has('identity.record_bsn')
            ->where('fund_id', $fund_id)
            ->get();

        if ($requests->isEmpty()) {
            $this->printText('No fund requests found!');
            $this->printSeparator();
            $this->askAction();

            return;
        }

        $proceed = $this->ask("{$requests->count()} fund requests found, proceed?", 'yes');

        if ($proceed === 'yes') {
            $this->processFundRequests($requests);
            $this->printStats();
        }
    }

    /**
     * @param Collection $requests
     * @throws Throwable
     * @return void
     */
    protected function processFundRequests(Collection $requests): void
    {
        $this->printText('Progress:');
        $bar = $this->output->createProgressBar($requests->count());
        $bar->start();

        $requests->each(function (FundRequest $request) use ($bar) {
            $this->updateFundRequest($request, $bar);
        });

        $bar->finish();
        $this->newLine();
        $this->printSeparator();
    }

    /**
     * @param FundRequest $request
     * @param ProgressBar $bar
     * @return void
     */
    protected function updateFundRequest(
        FundRequest $request,
        ProgressBar $bar
    ): void {
        $existingData = $this->getFundRequestRecordData($request);
        $newData = $this->getNewFundRequestRecordData($request);

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

        $this->printText("Found new records for request #$request->id:");
        $this->table(['Record', 'Value'], $this->diffArrayToTableValues($added));
        $this->newLine();

        $accepted = $this->acceptChangesFundRequest($request, $added);

        if ($accepted) {
            Event::dispatch(new FundRequestRecordsUpdatedEvent($request, 'add', $added));

            $this->addStatistic($request->id, added: $this->diffArrayToString($added));
        }

        $this->newLine();
        $bar->advance();
    }

    /**
     * @param FundRequest $request
     * @return array
     */
    protected function getFundRequestRecordData(FundRequest $request): array
    {
        return $this->normalizeData($request->records->mapWithKeys(fn (FundRequestRecord $record) => [
            $record->record_type->key => $record->value,
        ])->toArray());
    }

    /**
     * @param FundRequest $request
     * @return array|null
     */
    protected function getNewFundRequestRecordData(FundRequest $request): ?array
    {
        $fundPrefills = IConnectPrefill::getBsnApiPrefills($request->fund, $request->identity->bsn, withResponseData: true);

        if (is_array($fundPrefills['error'])) {
            $this->newLine();
            $this->printText("Fund request #$request->id:");
            $this->printText($this->red('Fetch from BRP failed: ' . Arr::get($fundPrefills, 'error.key')));
            $this->addStatistic($request->id, failed: Arr::get($fundPrefills, 'error.key'));

            return null;
        }

        $newData = $request->prepareRecords($fundPrefills);

        if (!$request->fund->recordsIsValidByCriteria($newData)) {
            $this->newLine();
            $this->printText("Fund request #$request->id:");
            $this->printText($this->red('Fetch from BRP failed: Invalid records for the fund'));
            $this->addStatistic($request->id, failed: 'Invalid records for the fund');

            return null;
        }

        return $this->normalizeData($newData);
    }

    /**
     * @param FundRequest $request
     * @param array $addedData
     * @return bool
     */
    protected function acceptChangesFundRequest(FundRequest $request, array $addedData): bool
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

            $accept = $this->ask('Would you like to update the fund request?', 1);
        }

        switch ($accept) {
            case 1:
                $request->addRecords($this->mapRequestRecords($addedData));
                $this->printText("Fund request #$request->id.");
                $this->stats['updated']++;

                return true;
            case 2:
                $this->acceptAll = true;
                $request->addRecords($this->mapRequestRecords($addedData));
                $this->printText("Fund request #$request->id.");
                $this->stats['updated']++;

                return true;
            case 3:
                $this->printText("Fund request #$request->id.");
                $this->stats['skipped_manual']++;

                return false;
            default:
                $this->printText("Invalid input!\nPlease try again:\n");

                return $this->acceptChangesFundRequest($request, $addedData);
        }
    }
}
