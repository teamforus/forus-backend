<?php

namespace App\Http\Resources;

use App\Models\Note;
use Illuminate\Http\Request;

/**
 * @property-read Note $resource
 */
class NoteResource extends BaseJsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray($request): array
    {
        return array_merge($this->resource->only('id', 'description'), [
            'employee' => $this->resource->employee ? [
                'id' => $this->resource->employee->id,
                'email' => $this->resource->employee->identity->email,
                'identity_address' => $this->resource->employee->identity_address,
            ] : null,
        ], $this->timestamps($this->resource, 'created_at'));
    }
}
