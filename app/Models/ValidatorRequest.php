<?php

namespace App\Models;

use App\Services\FileService\Traits\HasFiles;
use App\Services\Forus\Identity\Models\Identity;
use App\Services\Forus\Record\Models\Record;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Class ValidatorRequest
 * @property mixed $id
 * @property int $validator_id
 * @property int $record_id
 * @property string $state
 * @property string $identity_address
 * @property Identity $identity
 * @property Record $record
 * @property ProductRequest $product_request
 * @property Validator $validator
 * @property Carbon $validated_at
 * @package App\Models
 */
class ValidatorRequest extends Model
{
    use HasFiles;

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
    public function product_request() {
        return $this->belongsTo(ProductRequest::class);
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
