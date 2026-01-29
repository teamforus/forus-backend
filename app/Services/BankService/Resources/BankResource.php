<?php

namespace App\Services\BankService\Resources;

use App\Http\Resources\BaseJsonResource;
use App\Services\BankService\Models\Bank;
use Illuminate\Http\Request;

/**
 * @property-read Bank $resource
 */
class BankResource extends BaseJsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        return $this->resource->only('id', 'key', 'name');
    }
}
