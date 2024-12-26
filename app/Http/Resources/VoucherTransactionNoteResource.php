<?php

namespace App\Http\Resources;

use App\Models\VoucherTransactionNote;
use Illuminate\Http\Request;

/**
 * @property VoucherTransactionNote $resource
 */
class VoucherTransactionNoteResource extends BaseJsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        return array_merge($this->resource->only([
            'id', 'message', 'icon', 'group',
        ]), $this->timestamps($this->resource, 'created_at', 'created_at'));
    }
}
