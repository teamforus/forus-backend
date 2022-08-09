<?php

namespace App\Models;

use App\Models\Traits\HasDbTokens;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

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
 * @property \Illuminate\Support\Carbon|null $validated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Fund|null $fund
 * @property-read bool $is_used
 * @property-read \App\Models\Identity $identity
 * @property-read \App\Models\Organization|null $organization
 * @property-read EloquentCollection|\App\Models\PrevalidationRecord[] $prevalidation_records
 * @property-read int|null $prevalidation_records_count
 * @property-read EloquentCollection|\App\Models\PrevalidationRecord[] $records
 * @property-read int|null $records_count
 * @method static Builder|Prevalidation newModelQuery()
 * @method static Builder|Prevalidation newQuery()
 * @method static \Illuminate\Database\Query\Builder|Prevalidation onlyTrashed()
 * @method static Builder|Prevalidation query()
 * @method static Builder|Prevalidation whereCreatedAt($value)
 * @method static Builder|Prevalidation whereDeletedAt($value)
 * @method static Builder|Prevalidation whereExported($value)
 * @method static Builder|Prevalidation whereFundId($value)
 * @method static Builder|Prevalidation whereId($value)
 * @method static Builder|Prevalidation whereIdentityAddress($value)
 * @method static Builder|Prevalidation whereOrganizationId($value)
 * @method static Builder|Prevalidation whereRecordsHash($value)
 * @method static Builder|Prevalidation whereRedeemedByAddress($value)
 * @method static Builder|Prevalidation whereState($value)
 * @method static Builder|Prevalidation whereUid($value)
 * @method static Builder|Prevalidation whereUidHash($value)
 * @method static Builder|Prevalidation whereUpdatedAt($value)
 * @method static Builder|Prevalidation whereValidatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|Prevalidation withTrashed()
 * @method static \Illuminate\Database\Query\Builder|Prevalidation withoutTrashed()
 * @mixin \Eloquent
 */
class Prevalidation extends BaseModel
{
    use SoftDeletes, HasDbTokens;

    public const STATE_PENDING = 'pending';
    public const STATE_USED = 'used';

    public const STATES = [
        self::STATE_PENDING,
        self::STATE_USED
    ];

    /**
     * @var string[]
     */
    protected $dates = [
        'validated_at'
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
        'records_hash', 'uid_hash', 'validated_at',
    ];

    /**
     * @return BelongsTo
     */
    public function identity(): BelongsTo
    {
        return $this->belongsTo(Identity::class, 'identity_address', 'address');
    }

    /**
     * @param Identity $identity
     * @return void
     */
    public static function assignAvailableToIdentityByBsn(Identity $identity): void
    {
        if (!$identity->bsn) {
            return;
        }

        $query = static::where([
            'state' => static::STATE_PENDING,
        ])->whereRelation('fund.organization', [
            'bsn_enabled' => true,
        ]);

        /** @var Prevalidation[]|\Illuminate\Database\Eloquent\Collection $prevalidations */
        $prevalidations = $query->where(function (Builder $query) use ($identity) {
            // Where BSN record match
            $query->whereHas('prevalidation_records', fn(Builder $builder) => $builder->where([
                'value' => $identity->bsn,
            ])->whereRelation('record_type', 'key', '=', 'bsn'));

            // Or BSN hashed record match
            foreach (Fund::whereRelation('fund_config', 'hash_bsn', true)->get() as $fund) {
                $query->orWhere(static function(Builder $builder) use ($fund, $identity) {
                    $builder->where([
                        'fund_id' => $fund->id,
                    ])->whereHas('prevalidation_records', function(Builder $builder) use ($fund, $identity) {
                        $builder->where([
                            'value' => $fund->getHashedValue($identity->bsn),
                        ])->whereRelation('record_type', 'key', '=', 'bsn_hash');
                    });
                });
            }
        })->get();

        $prevalidations->each(static function(Prevalidation $prevalidation) use ($identity) {
            $prevalidation->assignToIdentity($identity);
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
        $prevalidations = self::whereIdentityAddress(auth()->id());

        if ($request->has('q') && $q = $request->input('q')) {
            $prevalidations->where(static function(Builder $query) use ($q) {
                $query->where('uid', 'like', "%$q%");
                $query->orWhereIn('id', static function(
                    \Illuminate\Database\Query\Builder $query
                ) use ($q) {
                    $query->from(
                        (new PrevalidationRecord)->getTable()
                    )->where(
                        'value', 'like', "%$q%"
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

        $query = self::search($request)->with([
            'prevalidation_records.record_type.translations',
        ]);

        $data = $query->get()->map(static function(Prevalidation $prevalidation) use ($transKey)  {
            return collect([
                trans("$transKey.code") => $prevalidation->uid,
                trans("$transKey.used") => trans("$transKey.used_" . ($prevalidation->state === self::STATE_USED ? "yes" : "no")),
            ])->merge($prevalidation->prevalidation_records->filter(function(PrevalidationRecord $record) {
                return !str_contains($record->record_type->key, '_eligible');
            })->pluck('value', 'record_type.name'))->toArray();
        })->values();

        (clone $query)->update([
            'exported' => true
        ]);

        return $data;
    }

    /**
     * @param Identity $identity
     * @return Prevalidation
     */
    public function assignToIdentity(Identity $identity): Prevalidation
    {
        foreach ($this->prevalidation_records as $record) {
            if ($record->record_type->key === 'bsn') {
                continue;
            }

            $record->makeRecord($identity)
                ->makeValidationRequest()
                ->approve($this->identity, $this->organization, $this);
        }

        return $this->updateModel([
            'state' => 'used',
            'redeemed_by_address' => $identity->address,
        ]);
    }

    /**
     * @param Identity $identity
     * @param Fund $fund
     * @param array $data
     * @param array $overwriteKeys
     * @return EloquentCollection
     */
    public static function storePrevalidations(
        Identity $identity,
        Fund $fund,
        array $data,
        array $overwriteKeys = []
    ): EloquentCollection {
        $primaryKeyName = $fund->fund_config->csv_primary_key;

        $recordTypes = array_pluck(record_types_cached(), 'id', 'key');
        $fundPrevalidationPrimaryKey = $recordTypes[$primaryKeyName] ?? abort(500);

        // list existing uid from fund prevalidations
        $existingPrimaryKeys = PrevalidationRecord::whereRelation('prevalidation', [
            'identity_address' => $identity->address,
            'fund_id' => $fund->id,
        ])->where([
            'record_type_id' => $fundPrevalidationPrimaryKey,
        ])->pluck('value');

        /** @var array[] $data new pre validations and pre validations that have to be updated */
        $data = array_filter(array_map(static function($record) use ($existingPrimaryKeys, $fund, $overwriteKeys, $recordTypes) {
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

        $prevalidations = array_map(static function(array $records) use ($fund, $overwriteKeys, $fundPrevalidationPrimaryKey, $identity) {
            if (in_array($records['primaryKey'], $overwriteKeys, true)) {
                $prevalidation = Prevalidation::where([
                    'state' => Prevalidation::STATE_PENDING,
                    'organization_id' => $fund->organization_id,
                    'fund_id' => $fund->id,
                    'identity_address' => $identity->address,
                ])->whereRelation('prevalidation_records', [
                    'record_type_id' => $fundPrevalidationPrimaryKey,
                    'value' => $records['primaryKey'],
                ])->first();

                $prevalidation->prevalidation_records()->delete();
            } else {
                $prevalidation = Prevalidation::create([
                    'uid' => self::makeNewUid(),
                    'state' => Prevalidation::STATE_PENDING,
                    'organization_id' => $fund->organization_id,
                    'fund_id' => $fund->id,
                    'identity_address' => $identity->address,
                    'validated_at' => now(),
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

    /**
     * @return bool
     * @noinspection PhpUnused
     */
    public function getIsUsedAttribute(): bool {
        return $this->state === self::STATE_USED;
    }

    /**
     * @param $code
     * @return static|null
     */
    public static function findByCode($code): ?self
    {
        return self::whereUid($code)->first();
    }

    /**
     * @return string
     */
    public static function makeNewUid(): string
    {
        return self::makeUniqueTokenCallback(function ($value) {
            return
                Prevalidation::whereUid($value)->doesntExist() &&
                Voucher::whereActivationCode('activation_code')->doesntExist();
        }, 4, 2);
    }
}
