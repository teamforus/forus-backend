<?php

namespace App\Http\Resources;

use App\Services\Forus\Identity\Models\IdentityEmail;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class IdentityEmailResource
 * @property IdentityEmail $resource
 * @package App\Http\Resources
 */
class IdentityEmailResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $identityEmail = $this->resource;

        return collect($identityEmail)->only([
            'id', 'identity_address', 'email', 'verified', 'primary',
        ])->merge([
            'created_at' => $identityEmail->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $identityEmail->updated_at->format('Y-m-d H:i:s'),
            'created_at_locale' => format_datetime_locale($identityEmail->created_at),
            'updated_at_locale' => format_datetime_locale($identityEmail->updated_at)
        ])->toArray();
    }
}
