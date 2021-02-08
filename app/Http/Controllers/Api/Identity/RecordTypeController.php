<?php

namespace App\Http\Controllers\Api\Identity;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\RecordTypes\IndexRecordTypesRequest;

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
     * @return array
     */
    public function index(IndexRecordTypesRequest $request): array
    {
        $insertableOnly = $request->input('insertable_only', false);
        $system = $request->input('insertable_only', false);
        $recordTypes = $request->records_repo()->getRecordTypes(!$insertableOnly && !$system);

        return array_values(array_map(function ($type) {
            return array_only($type, [
                'key', 'name', 'type', 'system'
            ]);
        }, $recordTypes));
    }
}
