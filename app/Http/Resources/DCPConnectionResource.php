<?php

namespace App\Http\Resources;

use App\Services\DCPService\Models\DCPConnection;
use Illuminate\Http\Request;

/**
 * @property DCPConnection $resource
 */
class DCPConnectionResource extends BaseJsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        $connection = $this->resource;

        return [
            ...$this->resource->only(['id', 'implementation_id', 'url', 'username']),
            'enabled' => (bool) $connection->enabled,
            'has_password' => !empty($connection->getAttributes()['password'] ?? null),
            'order_types' => (object) ($connection->order_types ?: []),
            'is_active' => $connection->exists && $connection->isActive(),
            ...$this->makeTimestamps($connection->only(['created_at', 'updated_at'])),
        ];
    }
}
