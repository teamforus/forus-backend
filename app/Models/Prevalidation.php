<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * App\Models\Prevalidation
 *
 * @property int $id
 * @property string|null $uid
 * @property string $identity_address
 * @property int|null $fund_id
 * @property int|null $organization_id
 * @property string $state
 * @property int $exported
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Fund|null $fund
 * @property-read string|null $created_at_locale
 * @property-read string|null $updated_at_locale
 * @property-read \App\Models\Organization|null $organization
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\PrevalidationRecord[] $prevalidation_records
 * @property-read int|null $prevalidation_records_count
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Prevalidation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Prevalidation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Prevalidation query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Prevalidation whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Prevalidation whereExported($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Prevalidation whereFundId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Prevalidation whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Prevalidation whereIdentityAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Prevalidation whereOrganizationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Prevalidation whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Prevalidation whereUid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Prevalidation whereUpdatedAt($value)
 * @mixin \Eloquent
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\PrevalidationRecord[] $records
 * @property-read int|null $records_count
 */
class Prevalidation extends Model
{
    const STATE_PENDING = 'pending';
    const STATE_USED = 'used';

    const STATES = [
        self::STATE_PENDING,
        self::STATE_USED
    ];

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
        'uid', 'identity_address', 'state', 'fund_id', 'organization_id',
        'exported',
    ];

    public static function assignAvailableToIdentityByBsn(string $identity_address)
    {
        $recordRepo = resolve('forus.services.record');
        $record_type_id = $recordRepo->getTypeIdByKey('bsn');

        if (!$bsn = $recordRepo->bsnByAddress($identity_address)) {
            return;
        }

        self::where([
            'state' => self::STATE_PENDING
        ])->whereHas('prevalidation_records', function(
            Builder $builder
        ) use ($record_type_id, $bsn) {
            $builder->where([
                'record_type_id' => $record_type_id,
                'value' => $bsn,
            ]);
        })->get()->each(function(
            Prevalidation $prevalidation
        ) use ($identity_address) {
            $prevalidation->assignToIdentity($identity_address);
        });
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function prevalidation_records() {
        return $this->hasMany(PrevalidationRecord::class);
    }

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
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function organization() {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @param Request $request
     * @return Builder
     */
    public static function search(
        Request $request
    ) {
        $identity_address = auth_address();

        $q = $request->input('q', null);
        $fund_id =$request->input('fund_id', null);
        $organization_id =$request->input('organization_id', null);
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

        if ($organization_id) {
            $prevalidations->where(compact('organization_id'));
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
            'prevalidation_records.record_type.translations'
        ])->get()->map(function(Prevalidation $prevalidation) {
            return collect([
                'code' => $prevalidation->uid
            ])->merge($prevalidation->prevalidation_records->filter(function($record) {
                return strpos($record->record_type->key, '_eligible') === false;
            })->pluck(
                'value', 'record_type.name'
            ))->toArray();
        })->values();
    }

    /**
     * @param $uid
     */
    public static function deactivateByUid($uid) {
        Prevalidation::where(compact('uid'))->update([
            'state' => Prevalidation::STATE_USED
        ]);
    }

    /**
     * @param $identity_address
     * @return Prevalidation
     */
    public function assignToIdentity($identity_address) {
        $recordRepo = resolve('forus.services.record');
        $bsnTypeId = $recordRepo->getTypeIdByKey('bsn');

        foreach($this->prevalidation_records as $record) {
            if ($record->record_type_id === $bsnTypeId) {
                continue;
            }

            /** @var $record PrevalidationRecord */
            $record = $recordRepo->recordCreate(
                $identity_address,
                $record->record_type->key,
                $record->value
            );

            $validationRequest = $recordRepo->makeValidationRequest(
                $identity_address,
                $record['id']
            );

            $recordRepo->approveValidationRequest(
                $this->identity_address,
                $validationRequest['uuid'],
                $this->organization_id
            );
        }

        return $this->updateModel([
            'state' => 'used'
        ]);
    }
}
