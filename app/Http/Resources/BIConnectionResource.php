<?php

namespace App\Http\Resources;

use App\Services\BIConnectionService\Models\BIConnection;
use Carbon\CarbonInterface;

/**
 * @property BIConnection $resource
 */
class BIConnectionResource extends BaseJsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|null
     */
    public function toArray($request): ?array
    {
        if (is_null($connection = $this->resource)) {
            return null;
        }

        return [
            ...$connection->only([
                'id', 'auth_type', 'access_token', 'expiration_period', 'data_types', 'ips',
                'organization_id', 'created_at', 'expire_at',
            ]),
            'expire_after_locale' => $connection->expire_at->diffForHumans(
                now(), CarbonInterface::DIFF_RELATIVE_TO_NOW,
            ),
            'expired' => $connection->isExpired(),
            ...$this->makeTimestamps($connection->only([
                'created_at', 'expire_at',
            ])),
        ];
    }
}
