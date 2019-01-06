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
     * @param $identity_address
     * @param string|null $q
     * @param int|null $fund_id
     * @param string|null $state
     * @param string|null $from
     * @param string|null $to
     * @param boolean|null $exported
     * @return \Illuminate\Database\Eloquent\Model
     */
    public static function search(
        $identity_address,
        string $q = null,
        int $fund_id = null,
        string $state = null,
        $from = null,
        $to = null,
        $exported = null
    ) {
        $prevalidations = Prevalidation::getModel()->where(compact(
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
                        PrevalidationRecord::getModel()->getTable()
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
     * @param $identity_address
     * @param string|null $q
     * @param int|null $fund_id
     * @param string|null $state
     * @param string|null $from
     * @param string|null $to
     * @param boolean|null $exported
     * @return \Illuminate\Database\Eloquent\Model
     */
    public static function export(
        $identity_address,
        string $q = null,
        int $fund_id = null,
        string $state = null,
        $from = null,
        $to = null,
        $exported = null
    ) {
        $query = self::search(
            $identity_address, $q, $fund_id, $state, $from, $to, $exported
        );

        $query->update([
            'exported' => true
        ]);

        $headers = [
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Content-type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename=prevalidations.csv',
            'Expires'             => '0',
            'Pragma'              => 'public'
        ];

        $list = $query->with([
            'records.record_type.translations'
        ])->get()->map(function(Prevalidation $prevalidation) {
            return collect([
                'code' => $prevalidation->uid
            ])->merge($prevalidation->records->pluck(
                'value', 'record_type.name'
            ))->toArray();
        })->toArray();

        // add headers for each column in the CSV download
        array_unshift($list, array_keys($list[0]));

        $callback = function() use ($list) {
            $fileHandler = fopen('php://output', 'w');

            foreach ($list as $row) {
                fputcsv($fileHandler, $row);
            }

            fclose($fileHandler);
        };

        return response()->stream($callback, 200, $headers);
    }
}
