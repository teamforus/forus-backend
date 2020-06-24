<?php

namespace App\Http\Controllers\Api\Identity;

use App\Http\Requests\Api\Records\IndexRecordsRequest;
use App\Http\Requests\Api\Records\RecordStoreRequest;
use App\Http\Requests\Api\Records\RecordUpdateRequest;
use App\Services\Forus\Record\Repositories\Interfaces\IRecordRepo;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class RecordController extends Controller
{
    private $recordRepo;

    public const HIDDEN_RECORD_TYPES = [
        'bsn'
    ];

    /**
     * RecordController constructor.
     * @param IRecordRepo $recordRepo
     */
    public function __construct(
        IRecordRepo $recordRepo
    ) {
        $this->recordRepo = $recordRepo;
    }

    /**
     * Get list records
     * @param IndexRecordsRequest $request
     * @return array
     */
    public function index(IndexRecordsRequest $request): array
    {
        return array_values(array_filter($this->recordRepo->recordsList(
            auth_address(),
            $request->get('type', null),
            $request->get('record_category_id', null),
            (bool) $request->get('deleted', false)
        ), static function($record) {
            return !in_array($record['key'], self::HIDDEN_RECORD_TYPES, true);
        }));
    }

    /**
     * Create new record
     * @param RecordStoreRequest $request
     * @return array|bool
     * @throws \Exception
     */
    public function store(RecordStoreRequest $request)
    {
        return $this->recordRepo->recordCreate(
            auth_address(),
            $request->get('type'),
            $request->get('value'),
            $request->get('record_category_id', null),
            $request->get('order', null)
        );
    }

    /**
     * Validate records store request
     * @param RecordStoreRequest $request
     */
    public function storeValidate(RecordStoreRequest $request): void {}

    /**
     * Get record
     * @param int $recordId
     * @return array
     */
    public function show(
        int $recordId
    ): array {
        $record = $this->recordRepo->recordGet(auth_address(), $recordId, true);

        if (empty($record) || in_array($record['key'], self::HIDDEN_RECORD_TYPES, true)) {
            abort(404, trans('records.codes.404'));
        }

        return $record;
    }

    /**
     * Update the specified resource in storage.
     *
     * @param RecordUpdateRequest $request
     * @param int $recordId
     * @return array
     */
    public function update(
        RecordUpdateRequest $request,
        int $recordId
    ): array {
        $identity = auth_address();

        if (empty($this->recordRepo->recordGet(
            $identity, $recordId
        ))) {
            abort(404, trans('records.codes.404'));
        }

        $success = $this->recordRepo->recordUpdate(
            auth_address(),
            $recordId,
            $request->input('record_category_id', null),
            $request->input('order', null)
        );

        return compact('success');
    }

    /**
     * Validate records update request
     * @param RecordUpdateRequest $request
     */
    public function updateValidate(RecordUpdateRequest $request): void {}

    /**
     * Delete record
     * @param int $recordId
     * @return array
     * @throws \Exception
     */
    public function destroy(
        int $recordId
    ): array {
        $identity = auth_address();

        if (empty($this->recordRepo->recordGet(
            $identity, $recordId
        ))) {
            abort(404, trans('records.codes.404'));
        }

        $success = $this->recordRepo->recordDelete(
            auth_address(),
            $recordId
        );

        return compact('success');
    }

    /**
     * Sort records
     * @param Request $request
     * @return array
     */
    public function sort(
        Request $request
    ): array {
        $this->recordRepo->recordsSort(
            auth_address(),
            collect($request->get('records', []))->toArray()
        );

        $success = true;

        return compact('success');
    }
}
