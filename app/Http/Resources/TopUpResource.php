<?php

namespace App\Http\Resources;

use App\Models\FundTopUp;
use Illuminate\Http\Resources\Json\Resource;

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
        /** @var FundTopUp $fundTopUp */
        $fundTopUp = $this->resource;
        $bunq = $fundTopUp->fund->getBunq();

        if (!$bunq) {
            abort(403, 'Top up for this fund not available yet.');
        }

        $bunqIban = $bunq->getBankAccountIban();

        return collect($fundTopUp)->only([
            'code', 'state'
        ])->merge([
            'iban' => e($bunqIban),
        ]);
    }
}
