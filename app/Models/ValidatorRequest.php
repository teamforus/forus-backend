<?php

namespace App\Models;

use App\Services\Forus\Identity\Models\Identity;
use App\Services\Forus\Record\Models\Record;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Class ValidatorRequest
 * @property mixed $id
 * @property int $validator_id
 * @property int $record_id
 * @property string $state
 * @property string $identity_address
 * @property Identity $identity
 * @property Record $record
 * @property Validator $validator
 * @property Carbon $validated_at
 * @package App\Models
 */
class ValidatorRequest extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'validator_id', 'identity_address', 'record_validation_uid',
        'record_id', 'state', 'validated_at'
    ];

    protected $dates = [
        'validated_at'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\belongsTo
     */
    public function identity() {
        return $this->belongsTo(
            Identity::class, 'identity_address', 'address'
        );
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\belongsTo
     */
    public function record() {
        return $this->belongsTo(Record::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\belongsTo
     */
    public function validator() {
        return $this->belongsTo(Validator::class);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function search(Request $request) {
        $validatorIds = Validator::query()->where(
            'identity_address',
            auth()->user()->getAuthIdentifier()
        )->pluck('id');

        $query = ValidatorRequest::query()->whereIn(
            'validator_id', $validatorIds
        );

        if ($state = $request->get('state')) {
            $query->where('state', $state);
        }

        if ($q = $request->get('q')) {
            $query->whereHas('record', function(Builder $builder) use (
                $q
            ) {
                $builder->where('value', 'like', "%$q%");
            });
        }

        return $query;
    }
}
