<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\Resource;

class FundRedeemPrevalidationResource extends Resource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $creatorFields = [];

        $prevalidation = $this->resource;

        $fundId = $prevalidation->fund->getFundId();
        if ($fundId == null) {
            $fundId = 1;
        }
        $collection['fund_id'] = $fundId;
        return $collection;
    }
}
