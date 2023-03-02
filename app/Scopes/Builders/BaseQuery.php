<?php


namespace App\Scopes\Builders;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

abstract class BaseQuery
{
    /**
     * Determine if the value is a query builder instance or a Closure.
     *
     * @param  mixed  $value
     * @return bool
     */
    protected static function isQueryable(mixed $value): bool
    {
        return $value instanceof Builder || $value instanceof Relation;
    }
}