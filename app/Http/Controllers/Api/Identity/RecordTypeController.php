<?php

namespace App\Http\Controllers\Api\Identity;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\RecordTypes\IndexRecordTypesRequest;
use App\Http\Resources\RecordTypeResource;
use App\Models\RecordType;
use Illuminate\Http\JsonResponse;

/**
 * Class RecordTypeController
 * @package App\Http\Controllers\Api\Identity
 */
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
        $insertableOnly = $request->input('insertable_only', false);
        $system = $request->input('system', false);
        $recordTypes = RecordType::searchQuery(!$insertableOnly || $system);

        return new JsonResponse(RecordTypeResource::queryCollection(
            $recordTypes
        )->toArray($request));
    }
}
