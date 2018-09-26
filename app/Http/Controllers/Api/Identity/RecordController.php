<?php

namespace App\Http\Controllers\Api\Identity;

use App\Http\Requests\Api\Records\RecordStoreRequest;
use App\Http\Requests\Api\Records\RecordUpdateRequest;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class RecordController extends Controller
{
    private $recordRepo;

    /**
     * RecordCategoryController constructor.
     */
    public function __construct() {
        $this->recordRepo = app()->make('forus.services.record');
    }

    /**
     * Get list records
     * @param Request $request
     * @return array
     */
    public function index(Request $request)
    {
        return $this->recordRepo->recordsList(
            auth()->user()->getAuthIdentifier(),
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
            auth()->user()->getAuthIdentifier(),
            $request->get('type'),
            $request->get('value'),
            $request->get('record_category_id', null),
            $request->get('order', null)
        );
    }

    /**
     * Get record
     * @param Request $request
     * @param int $recordId
     * @return array
     */
    public function show(
        Request $request,
        int $recordId
    ) {
        $identity = auth()->user()->getAuthIdentifier();

        if (empty($this->recordRepo->recordGet(
            $identity, $recordId
        ))) {
            abort(404, trans('records.codes.404'));
        }

        return $this->recordRepo->recordGet(
            auth()->user()->getAuthIdentifier(),
            $recordId
        );
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  RecordUpdateRequest  $request
     * @param  int  $recordId
     * @return \Illuminate\Http\Response
     */
    public function update(
        RecordUpdateRequest $request,
        int $recordId
    ) {
        $identity = auth()->user()->getAuthIdentifier();

        if (empty($this->recordRepo->recordGet(
            $identity, $recordId
        ))) {
            abort(404, trans('records.codes.404'));
        }

        $success = $this->recordRepo->recordUpdate(
            auth()->user()->getAuthIdentifier(),
            $recordId,
            $request->input('record_category_id', null),
            $request->input('order', null)
        );

        return compact('success');
    }

    /**
     * Delete record
     * @param Request $request
     * @param int $recordId
     * @return array
     * @throws \Exception
     */
    public function destroy(
        Request $request,
        int $recordId
    ) {
        $identity = auth()->user()->getAuthIdentifier();

        if (empty($this->recordRepo->recordGet(
            $identity, $recordId
        ))) {
            abort(404, trans('records.codes.404'));
        }

        $success = $this->recordRepo->recordDelete(
            auth()->user()->getAuthIdentifier(),
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
            auth()->user()->getAuthIdentifier(),
            collect($request->get('records', []))->toArray()
        );

        $success = true;

        return compact('success');
    }
}
