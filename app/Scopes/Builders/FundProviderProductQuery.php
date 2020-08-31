<?php


namespace App\Scopes\Builders;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class FundProviderProductQuery
 * @package App\Scopes\Builders
 */
class FundProviderProductQuery
{
    /**
     * @param Builder|SoftDeletes $builder
     * @return Builder|\Illuminate\Database\Query\Builder|SoftDeletes
     */
    public static function withTrashed(Builder $builder) {
        return $builder->withTrashed();
    }
}