<?php

namespace App\Http\Resources;

use App\Models\RecordValidation;

/**
 * @property-read RecordValidation $resource
 */
class RecordValidationResource extends BaseJsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request): array
    {
        $validation = $this->resource;

        return array_merge($this->resource->only([
            'id', 'state', 'identity_address', 'organization_id',
        ]), [
            'email' => $validation->organization ? null : $validation->identity?->email,
        ], $this->timestamps($validation, 'created_at', 'updated_at', 'validation_date'));
    }
}
