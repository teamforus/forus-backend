<?php

namespace App\Http\Resources;

use App\Models\PreCheckRecordSetting;
use Illuminate\Http\Request;

/**
 * @property-read PreCheckRecordSetting $resource
 */
class PreCheckRecordSettingResource extends BaseJsonResource
{
    public const array LOAD = [
        'fund.fund_config.implementation',
    ];

    public const array LOAD_NESTED = [
        'fund.logo' => MediaResource::class,
    ];

    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        $recordSetting = $this->resource;

        return array_merge($recordSetting->only('id', 'fund_id', 'description', 'impact_level', 'is_knock_out'), [
            'fund_name' => $recordSetting->fund->name,
            'fund_logo' => new MediaResource($recordSetting->fund->logo),
            'implementation_name' => $recordSetting->fund->getImplementation()->name,
            'implementation_url_webshop' => $recordSetting->fund->getImplementation()->url_webshop,
        ]);
    }
}
