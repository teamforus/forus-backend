<?php


namespace App\Scopes\Builders;

use Illuminate\Database\Eloquent\Builder;

class OfficeQuery
{
    /**
     * @param Builder $query
     * @param string $q
     * @return Builder
     */
    public static function queryDeepFilter(Builder $query, string $q = '') {
        return $query->where(function (Builder $query) use ($q) {
            $like = '%' . $q . '%';

            $query->where(
                'address','LIKE', $like
            )->orWhereHas('organization.business_type.translations', function(
                Builder $builder
            ) use ($like) {
                $builder->where('business_type_translations.name', 'LIKE', $like);
            })->orWhereHas('organization', function(Builder $builder) use ($like) {
                $builder->where('organizations.name', 'LIKE', $like);
            })->orWhereHas('organization', function(Builder $builder) use ($like) {
                $builder->where('organizations.email_public', true);
                $builder->where('organizations.email', 'LIKE', $like);
            })->orWhereHas('organization', function(Builder $builder) use ($like) {
                $builder->where('organizations.phone_public', true);
                $builder->where('organizations.phone', 'LIKE', $like);
            })->orWhereHas('organization', function(Builder $builder) use ($like) {
                $builder->where('organizations.website_public', true);
                $builder->where('organizations.website', 'LIKE', $like);
            });
        });
    }
}