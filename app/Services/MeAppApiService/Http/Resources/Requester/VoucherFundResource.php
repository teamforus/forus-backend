<?php

namespace App\Services\MeAppApiService\Http\Resources\Requester;

use App\Http\Resources\BaseJsonResource;
use App\Http\Resources\Small\MediaSmallResource;
use App\Models\Enums\FundType;
use App\Models\Fund;
use App\Services\MeAppApiService\Http\Resources\Shared\OrganizationResource;
use Illuminate\Http\Request;

/**
 * @property-read Fund $resource
 */
class VoucherFundResource extends BaseJsonResource
{
    public const array LOAD = [
        'fund_config.implementation',
    ];

    public const array LOAD_NESTED = [
        'logo' => MediaSmallResource::class,
        'organization' => OrganizationResource::class,
    ];

    /**
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        $fund = $this->resource;

        return [
            ...$fund->only([
                'id', 'name',
            ]),
            'type' => FundType::forFund($fund),
            ...$fund->translateColumns($fund->only([
                'name',
            ])),
            'url_webshop' => $fund->fund_config->implementation->url_webshop ?? null,
            'logo' => $fund->logo ? new MediaSmallResource($fund->logo) : null,
            'organization' => new OrganizationResource($fund->organization),
        ];
    }
}
