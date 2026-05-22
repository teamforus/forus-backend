<?php

namespace App\Http\Controllers\Api\Platform\Organizations;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\Organizations\PrevalidationRequestRecords\UpdatePrevalidationRequestRecordRequest;
use App\Http\Resources\PrevalidationRequestRecordResource;
use App\Models\Organization;
use App\Models\PrevalidationRequest;
use App\Models\PrevalidationRequestRecord;

class PrevalidationRequestRecordsController extends Controller
{
    /**
     * Update the specified resource in storage.
     *
     * @param UpdatePrevalidationRequestRecordRequest $request
     * @param Organization $organization
     * @param PrevalidationRequest $prevalidationRequest
     * @param PrevalidationRequestRecord $record
     * @return PrevalidationRequestRecordResource
     */
    public function update(
        UpdatePrevalidationRequestRecordRequest $request,
        Organization $organization,
        PrevalidationRequest $prevalidationRequest,
        PrevalidationRequestRecord $record,
    ): PrevalidationRequestRecordResource {
        $this->authorize('view', [$prevalidationRequest, $organization]);
        $this->authorize('updateAsSponsor', [$record, $prevalidationRequest, $organization]);

        $record->change($request->input('value'), $request->employee($organization));

        return PrevalidationRequestRecordResource::create($record);
    }
}
