<?php

namespace App\Http\Controllers\Api\Identity;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\RecordCategories\RecordCategoryStoreRequest;
use App\Http\Requests\Api\RecordCategories\RecordCategoryUpdateRequest;
use App\Http\Requests\BaseFormRequest;
use App\Http\Resources\RecordCategoryResource;
use App\Models\RecordCategory;
use Illuminate\Http\JsonResponse;

class RecordCategoryController extends Controller
{
    /**
     * Get list categories
     * @param BaseFormRequest $request
     * @return JsonResponse
     */
    public function index(BaseFormRequest $request): JsonResponse
    {
        $query = $request->identity()->record_categories()->orderBy('order');

        return new JsonResponse(RecordCategoryResource::queryCollection($query)->toArray($request));
    }

    /**
     * Create new record category
     * @param RecordCategoryStoreRequest $request
     * @return RecordCategoryResource
     */
    public function store(RecordCategoryStoreRequest $request): RecordCategoryResource
    {
        $recordCategory = $request->identity()->createRecordCategory(
            $request->get('name'),
            $request->input('order', 0)
        );

        return RecordCategoryResource::create($recordCategory);
    }

    /**
     * Get record category
     * @param BaseFormRequest $request
     * @param int $recordCategoryId
     * @return RecordCategoryResource
     */
    public function show(BaseFormRequest $request, int $recordCategoryId): RecordCategoryResource
    {
        $recordCategory = $request->identity()->record_categories()->where([
            'record_categories.id' => $recordCategoryId,
        ])->first();

        if (empty($recordCategory)) {
            abort(404, trans('record-categories.codes.404'));
        }

        return RecordCategoryResource::create($recordCategory);
    }

    /**
     * Update record category
     * @param RecordCategoryUpdateRequest $request
     * @param int $recordCategoryId
     * @return RecordCategoryResource
     */
    public function update(RecordCategoryUpdateRequest $request, int $recordCategoryId): RecordCategoryResource
    {
        $recordCategory = $request->identity()->record_categories()->where([
            'record_categories.id' => $recordCategoryId,
        ])->first();

        if (empty($recordCategory)) {
            abort(404, trans('record-categories.codes.404'));
        }

        $recordCategory->update($request->only('name', 'order'));

        return RecordCategoryResource::create($recordCategory);
    }

    /**
     * Delete record category
     *
     * @param BaseFormRequest $request
     * @param int $recordCategoryId
     * @return JsonResponse
     * @throws \Exception
     */
    public function destroy(BaseFormRequest $request, int $recordCategoryId): JsonResponse
    {
        /** @var RecordCategory|null $recordCategory */
        $recordCategory = $request->identity()->record_categories()->where([
            'record_categories.id' => $recordCategoryId,
        ])->first();

        if (empty($recordCategory)) {
            abort(404, trans('record-categories.codes.404'));
        }

        $recordCategory->records()->update([
            'record_category_id' => null,
        ]);

        return new JsonResponse([
            'success' => $recordCategory->delete(),
        ]);
    }

    /**
     * Sort record categories
     * @param BaseFormRequest $request
     * @return JsonResponse
     */
    public function sort(BaseFormRequest $request): JsonResponse
    {
        $orders = collect($request->get('categories', []))->toArray();

        foreach ($orders as $categoryId => $order) {
            $request->identity()->record_categories()->where([
                'record_categories.id' => $categoryId,
            ])->update(compact('order'));
        }

        return new JsonResponse(['success' => true]);
    }
}
