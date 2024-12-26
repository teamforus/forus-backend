<?php

namespace App\Http\Resources\Requester;

use App\Models\Implementation;
use Illuminate\Database\Eloquent\Builder;

class ProductResource extends \App\Http\Resources\ProductResource
{
    /**
     * @return Builder
     */
    protected function fundsQuery(): Builder
    {
        return Implementation::activeFundsQuery();
    }
}
