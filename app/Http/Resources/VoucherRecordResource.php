<?php

namespace App\Http\Resources;

use App\Http\Resources\Sponsor\SponsorVoucherRecordResource;
use Illuminate\Http\Request;

class VoucherRecordResource extends SponsorVoucherRecordResource
{
    /**
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        return $this->makeResource($this->resource);
    }
}
