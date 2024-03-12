<?php


namespace App\Scopes\Builders;

use App\Models\Office;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

class OfficeQuery
{
    /**
     * @param Office|Relation|Builder $query
     * @param string $q
     * @param bool $withBranches
     * @return Office|Relation|Builder
     */
    public static function queryWebshopDeepFilter(
        Office|Relation|Builder $query,
        string $q = '',
        bool $withBranches = false,
    ): Office|Relation|Builder {
        return $query->where(function (Builder $query) use ($q, $withBranches) {
            $query->where('address','LIKE', "%$q%");

            if ($withBranches) {
                $query->orWhere('branch_id', 'LIKE', "%$q%");
                $query->orWhere('branch_name', 'LIKE', "%$q%");
                $query->orWhere('branch_number', 'LIKE', "%$q%");
            }

            $query->orWhereHas('organization', function(Builder $query) use ($q) {
                $query->where('name', 'LIKE', "%$q%");

                $query->orWhereHas('business_type.translations', function(Builder $builder) use ($q) {
                    $builder->where('name', 'LIKE', "%$q%");
                });

                $query->orWhere(function(Builder $builder) use ($q) {
                    $builder->where('email_public', true);
                    $builder->where('email', 'LIKE', "%$q%");
                });

                $query->orWhere(function(Builder $builder) use ($q) {
                    $builder->where('phone_public', true);
                    $builder->where('phone', 'LIKE', "%$q%");
                });

                $query->orWhere(function(Builder $builder) use ($q) {
                    $builder->where('website_public', true);
                    $builder->where('website', 'LIKE', "%$q%");
                });
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