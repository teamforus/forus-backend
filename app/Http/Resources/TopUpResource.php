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

        $bunqIban = cache()->remember('bunq_iban', 1, function () {
            return app()->make('bunq')->getBankAccountIban();
        });

        return collect($fundTopUp)->only([
            'code', 'state'
        ])->merge([
            'iban' => e($bunqIban),
        ]);
    }
}
