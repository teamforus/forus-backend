<?php

namespace App\Http\Resources;

use App\Models\VoucherTransactionNote;
use Illuminate\Http\Resources\Json\Resource;

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

        /** @var VoucherTransactionNote $voucherTransaction */
        $voucherTransactionNote = $this->resource;

        return collect($voucherTransactionNote)->only([
            'id', 'message', 'icon', 'group', 'created_at', 'created_at',
            'created_at_locale', 'created_at_locale'
        ]);
    }
}
