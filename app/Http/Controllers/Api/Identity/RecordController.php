<?php

namespace App\Http\Controllers\Api\Identity;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Records\IndexRecordsRequest;
use App\Http\Requests\Api\Records\RecordStoreRequest;
use App\Http\Requests\Api\Records\RecordUpdateRequest;
use App\Http\Requests\Api\Records\SortRecordsRequest;
use App\Http\Requests\BaseFormRequest;
use App\Http\Resources\RecordResource;
use App\Models\Record;
use App\Models\RecordType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\JsonResponse;

/**
 * Class RecordController
 * @package App\Http\Controllers\Api\Identity
 */
class RecordController extends Controller
{
    /**
     * Get list records
     * @param IndexRecordsRequest $request
     * @return JsonResponse
     */
    public function index(IndexRecordsRequest $request): JsonResponse
    {
        $deleted = $request->get('deleted', false);

        /** @var Builder|Relation|SoftDeletes $query */
        $query = $request->identity()->records();
        $query = $deleted ? $query->onlyTrashed() : $query;

        $query = Record::search($query, $request->only([
            'type', 'record_category_id',
        ]), env('HIDE_SYSTEM_RECORDS', false));

        return new JsonResponse(RecordResource::queryCollection($query)->toArray($request));
    }

    /**
     * Create new record
     * @param RecordStoreRequest $request
     * @return RecordResource|null
     */
    public function store(RecordStoreRequest $request): ?RecordResource
    {
        return RecordResource::create($request->identity()->makeRecord(
            RecordType::findByKey($request->get('type')),
            $request->get('value'),
            $request->get('record_category_id'),
            $request->get('order')
        ));
    }

    /**
     * Validate records store request
     * @param RecordStoreRequest $request
     * @noinspection PhpUnused
     */
    public function storeValidate(RecordStoreRequest $request): void {}

    /**
     * Get record
     * @param BaseFormRequest $request
     * @param int $recordId
     * @return RecordResource
     */
    public function show(BaseFormRequest $request, int $recordId): RecordResource
    {
        /** @var Builder|SoftDeletes|Record $recordsQuery */
        $recordsQuery = $request->identity()->records();
        $record = $recordsQuery->withTrashed()->find($recordId);

        $hideSystemRecords = env('HIDE_SYSTEM_RECORDS', false);

        if (!$record || ($hideSystemRecords && ($record->record_type->system ?? true))) {
            abort(404, trans('records.codes.404'));
        }

        return RecordResource::create($record);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param RecordUpdateRequest $request
     * @param int $recordId
     * @return RecordResource
     */
    public function update(RecordUpdateRequest $request, int $recordId): RecordResource
    {
        /** @var Record $record */
        $record = $request->identity()->records()->find($recordId);

        if (!$record) {
            abort(404, trans('records.codes.404'));
        }

        $record->update($request->only('record_category_id', 'order'));

        return RecordResource::create($record);
    }

    /**
     * Validate records update request
     * @param RecordUpdateRequest $request
     * @noinspection PhpUnused
     */
    public function updateValidate(RecordUpdateRequest $request): void {}

    /**
     * Delete record
     * @param BaseFormRequest $request
     * @param int $recordId
     * @return JsonResponse
     */
    public function destroy(BaseFormRequest $request, int $recordId): JsonResponse
    {
        /** @var Record $record */
        $record = $request->identity()->records()->find($recordId);

        if (!$record) {
            abort(404, trans('records.codes.404'));
        }

        if ($record->record_type->key === 'primary_email') {
            abort(403,'record.exceptions.cant_delete_primary_email', [
                'record_type_name' => $record->record_type->name
            ]);
        }

        return new JsonResponse([
            'success' => (bool) $record?->delete(),
        ]);
    }

    /**
     * Sort records
     * @param SortRecordsRequest $request
     * @return JsonResponse
     */
    public function sort(SortRecordsRequest $request): JsonResponse
    {
        foreach ($request->get('records', []) as $order => $recordId) {
            $request->identity()->records()->where([
                'records.id' => $recordId,
            ])->update(compact('order'));
        }

        return new JsonResponse([
            'success' => true,
        ]);
    }
}
