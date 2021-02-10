<?php

namespace App\Http\Controllers\Api\Identity;

use App\Http\Requests\Api\RecordCategories\RecordCategoryStoreRequest;
use App\Http\Requests\Api\RecordCategories\RecordCategoryUpdateRequest;
use App\Http\Requests\BaseFormRequest;
use App\Http\Controllers\Controller;

class RecordCategoryController extends Controller
{
    private $recordRepo;

    /**
     * RecordCategoryController constructor.
     */
    public function __construct() {
        $this->recordRepo = resolve('forus.services.record');
    }

    /**
     * Get list categories
     * @param BaseFormRequest $request
     * @return array
     */
    public function index(BaseFormRequest $request): array
    {
        return $request->records_repo()->categoriesList($request->auth_address());
    }

    /**
     * Create new record category
     * @param RecordCategoryStoreRequest $request
     * @return array
     */
    public function store(RecordCategoryStoreRequest $request): array
    {
        $success = !!$this->recordRepo->categoryCreate(
            $request->auth_address(),
            $request->get('name'),
            $request->input('order', 0)
        );

        return compact('success');
    }

    /**
     * Get record category
     * @param BaseFormRequest $request
     * @param int $recordCategoryId
     * @return array|null
     */
    public function show(BaseFormRequest $request, int $recordCategoryId): ?array
    {
        if (empty($this->recordRepo->categoryGet($request->auth_address(), $recordCategoryId))) {
            abort(404, trans('record-categories.codes.404'));
        }

        $category = $this->recordRepo->categoryGet($request->auth_address(), $recordCategoryId);

        if (!$category) {
            abort(404, trans('record-categories.codes.404'));
        }

        return $category;
    }

    /**
     * Update record category
     * @param RecordCategoryUpdateRequest $request
     * @param int $recordCategoryId
     * @return array
     */
    public function update(RecordCategoryUpdateRequest $request, int $recordCategoryId): array
    {
        if (empty($this->recordRepo->categoryGet($request->auth_address(), $recordCategoryId))) {
            abort(404, trans('record-categories.codes.404'));
        }

        $success = $this->recordRepo->categoryUpdate(
            $request->auth_address(),
            $recordCategoryId,
            $request->input('name'),
            $request->input('order')
        );

        return compact('success');
    }

    /**
     * Delete record category
     * @param BaseFormRequest $request
     * @param int $recordCategoryId
     * @return array
     * @throws \Exception
     */
    public function destroy(BaseFormRequest $request, int $recordCategoryId ): array
    {
        if (empty($this->recordRepo->categoryGet($request->auth_address(), $recordCategoryId))) {
            abort(404, trans('record-categories.codes.404'));
        }

        return [
            'success' => $this->recordRepo->categoryDelete(
                $request->auth_address(),
                $recordCategoryId
            ),
        ];
    }

    /**
     * Sort record categories
     * @param BaseFormRequest $request
     * @return array
     */
    public function sort(BaseFormRequest $request): array
    {
        $this->recordRepo->categoriesSort(
            $request->auth_address(),
            collect($request->get('categories', []))->toArray()
        );

        $success = true;

        return compact('success');
    }
}
