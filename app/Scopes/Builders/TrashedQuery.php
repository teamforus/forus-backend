<?php


namespace App\Scopes\Builders;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Eloquent\SoftDeletes;

class TrashedQuery
{
    /**
     * @param Builder|SoftDeletes|Relation $query
     * @return Builder|SoftDeletes|Relation
     */
    public static function withTrashed($query) {
        return $query->withTrashed();
    }

    /**
     * @param Builder|SoftDeletes|Relation $query
     * @return Builder|SoftDeletes|Relation
     */
    public static function onlyTrashed($query) {
        return $query->onlyTrashed();
    }
}