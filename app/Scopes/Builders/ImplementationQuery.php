<?php


namespace App\Scopes\Builders;

use App\Models\Implementation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

class ImplementationQuery
{
    /**
     * @param Builder|Relation|Implementation $query
     * @param string $q
     * @return Builder|Relation|Implementation
     */
    public static function whereQueryFilter(
        Builder|Relation|Implementation $query,
        string $q,
    ): Builder|Relation|Implementation {
        return $query->where('name', 'LIKE', "%$q%");
    }
}