<?php

namespace App\Http\Resources;

use App\Http\Resources\Sponsor\SponsorVoucherRecordResource;

class VoucherRecordResource extends SponsorVoucherRecordResource
{
    /**
     * @param $request
     * @return array
     */
    public function toArray($request): array
    {
        return $this->makeResource($this->resource);
    }
}
