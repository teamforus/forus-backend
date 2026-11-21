<?php

namespace App\Services\MeAppApiService\Http\Resources\Provider;

use App\Http\Resources\BaseJsonResource;
use App\Models\DemoTransaction;
use App\Models\Enums\DemoTransactionState;
use Illuminate\Http\Request;

/**
 * @property-read DemoTransaction $resource
 */
class DemoTransactionResource extends BaseJsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'state' => DemoTransactionState::forTransaction($this->resource),
            ...$this->makeTimestamps($this->resource->only([
                'created_at',
            ])),
        ];
    }
}
