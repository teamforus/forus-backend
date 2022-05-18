<?php


namespace App\Scopes\Builders;

use Illuminate\Database\Eloquent\Builder;

class ImplementationQuery
{
    /**
     * @param Builder $query
     * @param string $q
     * @return Builder
     */
    public static function whereQueryFilter(Builder $query, string $q): Builder
    {
        return $query->where('name', 'LIKE', "%$q%");
    }
}