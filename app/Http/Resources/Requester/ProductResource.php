<?php

namespace App\Http\Resources\Requester;

use App\Models\Implementation;
use Illuminate\Database\Eloquent\Builder;

/**
 * Class ProductResource
 * @package App\Http\Resources
 */
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
