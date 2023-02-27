<?php


namespace App\Scopes\Builders;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Eloquent\SoftDeletes;

class TrashedQuery
{
    /**
     * @param Builder|SoftDeletes|Relation $query
     * @return Builder|Relation
     */
    public static function withTrashed(mixed $query): Builder|Relation
    {
        return $query->withTrashed();
    }

    /**
     * @param Builder|SoftDeletes|Relation $query
     * @return Builder|Relation
     */
    public static function onlyTrashed(mixed $query): Builder|Relation
    {
        return $query->onlyTrashed();
    }
}