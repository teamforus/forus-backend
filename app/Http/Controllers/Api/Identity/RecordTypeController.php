<?php

namespace App\Http\Controllers\Api\Identity;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\RecordTypes\IndexRecordTypesRequest;
use App\Http\Resources\RecordTypeResource;
use App\Models\RecordType;
use Illuminate\Http\JsonResponse;

class RecordTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param IndexRecordTypesRequest $request
     * @return JsonResponse
     */
    public function index(IndexRecordTypesRequest $request): JsonResponse
    {
        $query = RecordType::searchQuery($request->only([
            'vouchers', 'criteria', 'organization_id',
        ]));

        $recordTypesCollection = RecordTypeResource::queryCollection($query, 1000);

        return new JsonResponse($recordTypesCollection->toArray($request));
    }
}
