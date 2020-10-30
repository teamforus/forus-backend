<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

/**
 * App\Models\Prevalidation
 *
 * @property int $id
 * @property string|null $uid
 * @property string $identity_address
 * @property string|null $redeemed_by_address
 * @property int|null $fund_id
 * @property int|null $organization_id
 * @property string $state
 * @property string|null $uid_hash
 * @property string|null $records_hash
 * @property int $exported
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Fund|null $fund
 * @property-read \App\Models\Organization|null $organization
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\PrevalidationRecord[] $prevalidation_records
 * @property-read int|null $prevalidation_records_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\PrevalidationRecord[] $records
 * @property-read int|null $records_count
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Prevalidation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Prevalidation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Prevalidation query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Prevalidation whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Prevalidation whereExported($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Prevalidation whereFundId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Prevalidation whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Prevalidation whereIdentityAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Prevalidation whereOrganizationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Prevalidation whereRecordsHash($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Prevalidation whereRedeemedByAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Prevalidation whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Prevalidation whereUid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Prevalidation whereUidHash($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Prevalidation whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Prevalidation extends Model
{
    public const STATE_PENDING = 'pending';
    public const STATE_USED = 'used';

    public const STATES = [
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
        'uid', 'identity_address', 'redeemed_by_address', 'state',
        'fund_id', 'organization_id', 'exported', 'json',
        'records_hash', 'uid_hash',
    ];

    /**
     * @param string $identity_address
     */
    public static function assignAvailableToIdentityByBsn(
        string $identity_address
    ): void {
        $recordRepo = resolve('forus.services.record');
        $bsn_type_id = $recordRepo->getTypeIdByKey('bsn');
        $bsn_hash_type_id = $recordRepo->getTypeIdByKey('bsn_hash');

        if (!$bsn = $recordRepo->bsnByAddress($identity_address)) {
            return;
        }

        $funds_with_hashed_bsn = Fund::whereHas('fund_config', static function(Builder $builder) {
            $builder->where('hash_bsn', '=', true);
        })->with('fund_config')->get();

        /** @var Builder $query */
        $query = self::whereState(self::STATE_PENDING);

        $query->where(static function (
            Builder $query
        ) use ($bsn_type_id, $bsn_hash_type_id, $bsn, $funds_with_hashed_bsn) {
            $query->whereHas('prevalidation_records', static function(
                Builder $builder
            ) use ($bsn_type_id, $bsn) {
                $builder->where([
                    'record_type_id' => $bsn_type_id,
                    'value' => $bsn,
                ]);
            });

            foreach ($funds_with_hashed_bsn as $fund) {
                $query->orWhere(static function(Builder $builder) use ($bsn_hash_type_id, $bsn, $fund) {
                    $builder->where([
                        'fund_id' => $fund->id,
                    ])->whereHas('prevalidation_records', static function(
                        Builder $builder
                    ) use ($bsn_hash_type_id, $bsn, $fund) {
                        $builder->where([
                            'record_type_id' => $bsn_hash_type_id,
                            'value' => $fund->getHashedValue($bsn),
                        ]);
                    });
                });
            }
        });

        $query->get()->each(static function(Prevalidation $prevalidation) use ($identity_address) {
            $prevalidation->assignToIdentity($identity_address);
        });
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function prevalidation_records(): HasMany {
        return $this->hasMany(PrevalidationRecord::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function records(): HasMany {
        return $this->hasMany(PrevalidationRecord::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function fund(): BelongsTo {
        return $this->belongsTo(Fund::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function organization(): BelongsTo {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @param Request $request
     * @return Builder
     */
    public static function search(Request $request): Builder {
        /** @var Builder $prevalidations */
        $prevalidations = self::whereIdentityAddress(auth_address());

        if ($request->has('q') && $q = $request->input('q')) {
            $prevalidations->where(static function(Builder $query) use ($q) {
                $query->where('uid', 'like', "%{$q}%");
                $query->orWhereIn('id', static function(
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

        if ($request->has('fund_id') && $fund_id = $request->input('fund_id')) {
            $prevalidations->where(compact('fund_id'));
        }

        if ($request->has('organization_id')) {
            $prevalidations->where($request->only('organization_id'));
        }

        if ($request->has('state') && $state = $request->input('state')) {
            $prevalidations->where('state', $state);
        }

        if ($request->has('from') && $carbonFrom = Carbon::make($request->input('from'))) {
            $prevalidations->where('created_at', '>', $carbonFrom->startOfDay());
        }

        if ($request->has('to') && $carbonTo = Carbon::make($request->input('to'))) {
            $prevalidations->where('created_at', '<', $carbonTo->endOfDay());
        }

        if ($request->has('exported')) {
            $prevalidations->where('exported', '=', $request->input('exported'));
        }

        return $prevalidations;
    }

    /**
     * @param Request $request
     * @return \Illuminate\Support\Collection
     */
    public static function export(Request $request): Collection {
        $transKey = "export.prevalidations";

        $query = self::search($request);
        $query->update([
            'exported' => true
        ]);

        return $query->with([
            'prevalidation_records.record_type.translations'
        ])->get()->map(static function(Prevalidation $prevalidation) use ($transKey)  {
            return collect([
                trans("$transKey.code") => $prevalidation->uid,
                trans("$transKey.used") => $prevalidation->state === self::STATE_USED ?
                    trans("$transKey.used_yes") : trans("$transKey.used_no"),
            ])->merge($prevalidation->prevalidation_records->filter(static function(
                PrevalidationRecord $record
            ) {
                return strpos($record->record_type->key, '_eligible') === false;
            })->pluck(
                'value', 'record_type.name'
            ))->toArray();
        })->values();
    }

    /**
     * @param $uid
     */
    public static function deactivateByUid($uid): void {
        self::where(compact('uid'))->update([
            'state' => self::STATE_USED
        ]);
    }

    /**
     * @param $identity_address
     * @return Prevalidation
     */
    public function assignToIdentity($identity_address): Prevalidation {
        $recordRepo = resolve('forus.services.record');
        $bsnTypeId = $recordRepo->getTypeIdByKey('bsn');

        foreach ($this->prevalidation_records as $record) {
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
            'state' => 'used',
            'redeemed_by_address' => $identity_address
        ]);
    }

    /**
     * @param Fund $fund
     * @param array $data
     * @param array $overwriteKeys
     * @return EloquentCollection
     */
    public static function storePrevalidations(
        Fund $fund,
        array $data,
        array $overwriteKeys = []
    ): EloquentCollection {
        $identity_address = auth_address();
        $primaryKeyName = $fund->fund_config->csv_primary_key;

        $recordTypes = array_pluck(record_types_cached(), 'id', 'key');
        $fundPrevalidationPrimaryKey = $recordTypes[$primaryKeyName] ?? abort(500);

        // list existing uid from fund prevalidations
        $existingPrimaryKeys = PrevalidationRecord::whereHas('prevalidation', static function(
            Builder $builder
        ) use ($identity_address, $fund) {
            $builder->where([
                'identity_address' => $identity_address,
                'fund_id' => $fund->id,
            ]);
        })->where([
            'record_type_id' => $fundPrevalidationPrimaryKey,
        ])->pluck('value');

        /** @var array[] $data new pre validations and pre validations that have to be updated */
        $data = array_filter(array_map(static function($record) use (
            $existingPrimaryKeys, $fund, $overwriteKeys, $recordTypes
        ) {
            $primaryKey = $record[$fund->fund_config->csv_primary_key];

            if ($existingPrimaryKeys->search($primaryKey) !== false &&
                !in_array($primaryKey, $overwriteKeys, true)) {
                return null;
            }

            return [
                'primaryKey' => $primaryKey,
                'record' => $record,
                'records' => array_filter(array_map(static function($key) use ($recordTypes, $record) {
                    return (!$recordTypes[$key] || $key === 'primary_email') ? false : [
                        'record_type_id' => $recordTypes[$key],
                        'value' => is_null($record[$key]) ? '' : $record[$key]
                    ];
                }, array_keys($record)), static function($value) {
                    return (bool) $value;
                }),
            ];
        }, $data), "is_array");

        /** @var Prevalidation[] $prevalidations Newly created and updated pre validations */
        $prevalidations = array_map(static function(array $records) use (
            $fund, $overwriteKeys, $identity_address, $fundPrevalidationPrimaryKey
        ) {
            if (in_array($records['primaryKey'], $overwriteKeys, true)) {
                /** @var Prevalidation $prevalidation */
                $prevalidation = Prevalidation::where([
                    'state' => Prevalidation::STATE_PENDING,
                    'organization_id' => $fund->organization_id,
                    'fund_id' => $fund->id,
                    'identity_address' => $identity_address
                ])->whereHas('prevalidation_records', static function(
                    Builder $builder
                ) use ($records, $fundPrevalidationPrimaryKey) {
                    $builder->where([
                        'record_type_id' => $fundPrevalidationPrimaryKey,
                        'value' => $records['primaryKey'],
                    ]);
                })->first();

                $prevalidation->prevalidation_records()->delete();
            } else {
                /** @var Prevalidation $prevalidation */
                $prevalidation = Prevalidation::create([
                    'uid' => token_generator_db(Prevalidation::query(), 'uid', 4, 2),
                    'state' => Prevalidation::STATE_PENDING,
                    'organization_id' => $fund->organization_id,
                    'fund_id' => $fund->id,
                    'identity_address' => $identity_address
                ]);
            }

            $prevalidation->prevalidation_records()->createMany($records['records']);

            return $prevalidation->updateHashes();
        }, $data);

        return new EloquentCollection($prevalidations);
    }

    /**
     * @return $this
     */
    public function updateHashes(): Prevalidation {
        $records = $this->prevalidation_records->pluck(
            'value', 'record_type.key'
        )->toArray();

        $primaryKey = $records[$this->fund->fund_config->csv_primary_key];
        ksort($records);

        $this->update([
            'uid_hash' => hash('sha256', $primaryKey),
            'records_hash' => hash('sha256', json_encode($records)),
        ]);

        return $this;
    }
}
