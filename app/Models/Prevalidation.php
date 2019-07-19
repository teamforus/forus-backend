<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

/**
 * Class Prevalidation
 * @property int $id
 * @property int $fund_id
 * @property string $uid
 * @property string $identity_address
 * @property string $state
 * @property Fund $fund
 * @property Collection $records
 * @property boolean $exported
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
        'uid', 'identity_address', 'state', 'fund_id', 'exported'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function records() {
        return $this->hasMany(PrevalidationRecord::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function fund() {
        return $this->belongsTo(Fund::class);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Database\Eloquent\Model
     */
    public static function search(
        Request $request
    ) {
        $identity_address = $request->user();

        $q = $request->input('q', null);
        $fund_id =$request->input('fund_id', null);
        $state = $request->input('state', null);
        $from = $request->input('from', null);
        $to = $request->input('to', null);
        $exported = $request->input('exported', null);

        $prevalidations = Prevalidation::query()->where(compact(
            'identity_address'
        ));

        if ($q) {
            $prevalidations->where(function(Builder $query) use ($q) {
                $query->where(
                    'uid', 'like', "%{$q}%"
                )->orWhereIn('id', function(
                    \Illuminate\Database\Query\Builder $query
                ) use ($q) {
                    $query->from(
                        (new PrevalidationRecord)->getTable()
                    )->where(
                        'value', 'like', "%{$q}%"
                    )->select('prevalidation_id');
                });
            });
        }

        if ($fund_id) {
            $prevalidations->where(compact('fund_id'));
        }

        if ($state) {
            $prevalidations->where('state', $state);
        }

        if ($from) {
            $prevalidations->where(
                'created_at', '>', Carbon::make($from)->startOfDay()
            );
        }

        if ($exported !== null) {
            $prevalidations->where('exported', '=', $exported);
        }

        if ($to) {
            $prevalidations->where(
                'created_at', '<', Carbon::make($to)->endOfDay()
            );
        }

        return $prevalidations;
    }

    /**
     * @param Request $request
     * @return \Illuminate\Support\Collection
     */
    public static function export(Request $request) {
        $query = self::search($request);

        $query->update([
            'exported' => true
        ]);

        return $query->with([
            'records.record_type.translations'
        ])->get()->map(function(Prevalidation $prevalidation) {
            return collect([
                'code' => $prevalidation->uid
            ])->merge($prevalidation->records->filter(function($record) {
                return strpos($record->record_type->key, '_eligible') === false;
            })->pluck(
                'value', 'record_type.name'
            ))->toArray();
        })->values();
    }
}
