<?php

namespace App\Http\Resources;

use App\Models\FundRequestMissedRecord;
use Illuminate\Http\Request;
use Throwable;

/**
 * @property FundRequestMissedRecord $resource
 */
class FundRequestMissedRecordResource extends BaseJsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @throws Throwable
     * @return array
     */
    public function toArray(Request $request): array
    {
        return $this->resource->only([
            'id', 'type', 'group', 'field',
        ]);
    }
}
