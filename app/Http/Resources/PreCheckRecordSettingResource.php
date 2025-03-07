<?php

namespace App\Http\Resources;

use App\Models\PreCheckRecordSetting;

/**
 * @property-read PreCheckRecordSetting $resource
 */
class PreCheckRecordSettingResource extends BaseJsonResource
{
    public const array LOAD = [
        'fund.logo.presets',
        'fund.fund_config.implementation',
    ];

    /**
     * Transform the resource into an array.
     *
     * @param $request
     * @return array
     */
    public function toArray($request): array
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
