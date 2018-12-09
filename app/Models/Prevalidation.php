<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Class Prevalidation
 * @property int $id
 * @property string $uid
 * @property string $identity_address
 * @property string $state
 * @property Collection $records
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @package App\Models
 */
class Prevalidation extends Model
{
    /**
     * The number of models to return for pagination.
     *
     * @var int
     */
    protected $perPage = 10;

    /**
     * @var array
     */
    protected $fillable = [
        'uid', 'identity_address', 'state'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function records() {
        return $this->hasMany(PrevalidationRecord::class);
    }

    /**
     * @param $identity_address
     * @param bool $q
     * @return \Illuminate\Database\Eloquent\Model
     */
    public static function search($identity_address, $q = false) {
        $prevalidations = Prevalidation::getModel()->where(compact(
            'identity_address'
        ));

        if (!$q) {
            return $prevalidations;
        }

        return $prevalidations->where(function(Builder $query) use ($q) {
            $query->where(
                'uid', 'like', "%{$q}%"
            )->orWhereIn('id', function(
                \Illuminate\Database\Query\Builder $query
            ) use ($q) {
                $query->from(
                    PrevalidationRecord::getModel()->getTable()
                )->where(
                    'value', 'like', "%{$q}%"
                )->select('prevalidation_id');
            });
        });
    }
}
