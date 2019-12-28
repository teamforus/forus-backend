<?php

namespace App\Models;

use App\Services\Forus\Record\Models\Record;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * App\Models\ValidatorRequest
 *
 * @property int $id
 * @property int $validator_id
 * @property string|null $record_validation_uid
 * @property string $identity_address
 * @property int $record_id
 * @property string $state
 * @property \Illuminate\Support\Carbon $validated_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read string|null $created_at_locale
 * @property-read string|null $updated_at_locale
 * @property-read \App\Services\Forus\Record\Models\Record $record
 * @property-read \App\Models\Validator $validator
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ValidatorRequest newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ValidatorRequest newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ValidatorRequest query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ValidatorRequest whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ValidatorRequest whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ValidatorRequest whereIdentityAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ValidatorRequest whereRecordId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ValidatorRequest whereRecordValidationUid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ValidatorRequest whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ValidatorRequest whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ValidatorRequest whereValidatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ValidatorRequest whereValidatorId($value)
 * @mixin \Eloquent
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

    /**
     * @param Request $request
     * @param array $with
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator|LengthAwarePaginator
     */
    public static function searchPaginate(Request $request, $with = []) {
        $query = static::search($request);
        $per_page = $request->input('per_page', 10);

        if (!$group = $request->input('group', false)) {
            return $query->with($with)->paginate($per_page);
        }

        $addresses = array_pluck(
            $query
                ->groupBy('identity_address')
                ->orderBy('created_at')->with($with)
                ->paginate($per_page)->items(),
            'identity_address'
        );

        $data = static::search($request)
            ->whereIn('identity_address', $addresses)
            ->orderBy('id')->with($with)->get();

        return new LengthAwarePaginator(
            $data->groupBy('identity_address')->values(),
            static::search($request)->distinct()->count('identity_address'),
            $per_page
        );
    }
}
