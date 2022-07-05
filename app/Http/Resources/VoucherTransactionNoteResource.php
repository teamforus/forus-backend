<?php

namespace App\Http\Resources;

use App\Models\VoucherTransactionNote;

/**
 * Class VoucherTransactionNoteResource
 * @property VoucherTransactionNote $resource
 * @package App\Http\Resources
 */
class VoucherTransactionNoteResource extends BaseJsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        return array_merge($this->resource->only([
            'id', 'message', 'icon', 'group',
        ]), $this->timestamps($this->resource, 'created_at', 'created_at'));
    }
}
