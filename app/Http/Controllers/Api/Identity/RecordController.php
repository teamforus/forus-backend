<?php

namespace App\Http\Controllers\Api\Identity;

use App\Http\Requests\Api\Records\RecordStoreRequest;
use App\Http\Requests\Api\Records\RecordUpdateRequest;
use App\Services\Forus\Record\Repositories\Interfaces\IRecordRepo;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class RecordController extends Controller
{
    private $recordRepo;

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
     * @param Request $request
     * @return array
     */
    public function index(Request $request)
    {
        return $this->recordRepo->recordsList(
            auth_address(),
            $request->get('type', null),
            $request->get('record_category_id', null)
        );
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
    public function storeValidate(RecordStoreRequest $request) {}

    /**
     * Get record
     * @param int $recordId
     * @return array
     */
    public function show(
        int $recordId
    ) {
        $identity = auth_address();

        if (empty($this->recordRepo->recordGet(
            $identity, $recordId
        ))) {
            abort(404, trans('records.codes.404'));
        }

        return $this->recordRepo->recordGet(
            auth_address(),
            $recordId
        );
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
    ) {
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
    public function updateValidate(RecordUpdateRequest $request) {}

    /**
     * Delete record
     * @param int $recordId
     * @return array
     * @throws \Exception
     */
    public function destroy(
        int $recordId
    ) {
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
    ) {
        $this->recordRepo->recordsSort(
            auth_address(),
            collect($request->get('records', []))->toArray()
        );

        $success = true;

        return compact('success');
    }
}
