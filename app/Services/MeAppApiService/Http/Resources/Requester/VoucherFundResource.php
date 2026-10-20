<?php

namespace App\Services\MeAppApiService\Http\Resources\Requester;

use App\Http\Resources\BaseJsonResource;
use App\Models\Fund;
use App\Services\MeAppApiService\Http\Resources\Shared\MediaResource;
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
        'logo' => MediaResource::class,
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
                'id', 'name', 'type',
            ]),
            ...$fund->translateColumns($fund->only([
                'name',
            ])),
            'url_webshop' => $fund->fund_config->implementation->url_webshop ?? null,
            'logo' => $fund->logo ? new MediaResource($fund->logo) : null,
            'organization' => new OrganizationResource($fund->organization),
        ];
    }
}
