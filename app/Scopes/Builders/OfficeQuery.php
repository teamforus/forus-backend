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
    public static function queryDeepFilter(Builder $query, string $q = ''): Builder
    {
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

    /**
     * @param Builder $query
     * @param float $distance
     * @param array $location
     * @return Builder
     */
    public static function whereDistance(Builder $query, float $distance, array $location): Builder
    {
        $lng = number_format($location['lng'], 6, '.', '');
        $lat = number_format($location['lat'], 6, '.', '');
        $distance = number_format($distance, 2, '.', '');

        return $query->where(function(Builder $builder) use ($distance, $lng, $lat) {
            $builder->whereRaw("6371 * acos(cos(radians(" . $lat . "))
                * cos(radians(lat)) 
                * cos(radians(lon) - radians(" . $lng . ")) 
                + sin(radians(" . $lat . ")) 
                * sin(radians(lat))) < " . $distance);
        });
    }
}