<?php


namespace App\Scopes\Builders;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;

class TrashedQuery
{
    /**
     * @param Builder|SoftDeletes $query
     * @return Builder
     */
    public static function withTrashed(Builder $query): Builder {
        return $query->withTrashed();
    }
}