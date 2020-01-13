<?php

namespace App\Http\Resources;

use App\Models\VoucherTransactionNote;
use Illuminate\Http\Resources\Json\Resource;

/**
 * Class VoucherTransactionNoteResource
 * @property VoucherTransactionNote $resource
 * @package App\Http\Resources
 */
class VoucherTransactionNoteResource extends Resource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return collect($this->resource)->only([
            'id', 'message', 'icon', 'group', 'created_at', 'created_at'
        ])->merge([
            'created_at_locale' => $this->resource->created_at_locale,
            'updated_at_locale' => $this->resource->updated_at_locale,
        ])->toArray();
    }
}
