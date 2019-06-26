<?php

namespace App\Http\Resources;

use App\Models\FundTopUp;
use Illuminate\Http\Resources\Json\Resource;

/**
 * Class TopUpResource
 * @property FundTopUp $resource
 * @package App\Http\Resources
 */
class TopUpResource extends Resource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array|mixed
     * @throws \Exception
     */
    public function toArray($request)
    {
        if (!$bunq = $this->resource->fund->getBunq()) {
            abort(403, 'Top up for this fund not available yet.');
        }

        return collect($this->resource)->only([
            'code', 'state'
        ])->merge([
            'iban' => e($bunq->getBankAccountIban()),
        ]);
    }
}
