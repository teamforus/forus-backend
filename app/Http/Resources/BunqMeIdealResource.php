<?php

namespace App\Http\Resources;

use App\Models\BunqMeTab;
use Illuminate\Http\Resources\Json\Resource;

/**
 * Class BunqMeIdealRequestResource
 * @property BunqMeTab $resource
 * @package App\Http\Resources
 */
class BunqMeIdealResource extends Resource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     * @throws \Exception
     */
    public function toArray($request)
    {
        return collect($this->resource)->only([
            'id', 'amount', 'status', 'fund_id', 'created_at', 'updated_at',
        ])->toArray();
    }
}
