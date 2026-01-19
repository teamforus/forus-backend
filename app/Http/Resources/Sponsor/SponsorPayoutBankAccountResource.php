<?php

namespace App\Http\Resources\Sponsor;

use App\Http\Resources\BaseJsonResource;
use App\Models\FundRequest;
use Illuminate\Http\Request;

/**
 * @property FundRequest $resource
 */
class SponsorPayoutBankAccountResource extends BaseJsonResource
{
    /**
     * @var array
     */
    public const array LOAD = [
        'records',
        'fund.fund_config',
    ];

    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        $fundRequest = $this->resource;

        return [
            'id' => $fundRequest->id,
            'iban' => $fundRequest->getIban(false),
            'iban_name' => $fundRequest->getIbanName(false),
            ...$this->makeTimestamps([
                'created_at' => $fundRequest->created_at,
            ]),
        ];
    }
}
