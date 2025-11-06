<?php

namespace App\Models;

use App\Models\Traits\HasDbTokens;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

/**
 * App\Models\Prevalidation.
 *
 * @property int $id
 * @property string|null $uid
 * @property string $identity_address
 * @property int|null $employee_id
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
 * @property-read \App\Models\Employee|null $employee
 * @property-read \App\Models\Fund|null $fund
 * @property-read bool $is_used
 * @property-read \App\Models\Identity $identity
 * @property-read \App\Models\Organization|null $organization
 * @property-read EloquentCollection|\App\Models\PrevalidationRecord[] $prevalidation_records
 * @property-read int|null $prevalidation_records_count
 * @property-read EloquentCollection|\App\Models\PrevalidationRecord[] $records
 * @property-read int|null $records_count
 * @method static Builder<static>|Prevalidation newModelQuery()
 * @method static Builder<static>|Prevalidation newQuery()
 * @method static Builder<static>|Prevalidation onlyTrashed()
 * @method static Builder<static>|Prevalidation query()
 * @method static Builder<static>|Prevalidation whereCreatedAt($value)
 * @method static Builder<static>|Prevalidation whereDeletedAt($value)
 * @method static Builder<static>|Prevalidation whereEmployeeId($value)
 * @method static Builder<static>|Prevalidation whereExported($value)
 * @method static Builder<static>|Prevalidation whereFundId($value)
 * @method static Builder<static>|Prevalidation whereId($value)
 * @method static Builder<static>|Prevalidation whereIdentityAddress($value)
 * @method static Builder<static>|Prevalidation whereOrganizationId($value)
 * @method static Builder<static>|Prevalidation whereRecordsHash($value)
 * @method static Builder<static>|Prevalidation whereRedeemedByAddress($value)
 * @method static Builder<static>|Prevalidation whereState($value)
 * @method static Builder<static>|Prevalidation whereUid($value)
 * @method static Builder<static>|Prevalidation whereUidHash($value)
 * @method static Builder<static>|Prevalidation whereUpdatedAt($value)
 * @method static Builder<static>|Prevalidation whereValidatedAt($value)
 * @method static Builder<static>|Prevalidation withTrashed()
 * @method static Builder<static>|Prevalidation withoutTrashed()
 * @mixin \Eloquent
 */
class Prevalidation extends Model
{
    use SoftDeletes;
    use HasDbTokens;

    public const string STATE_PENDING = 'pending';
    public const string STATE_USED = 'used';

    public const array STATES = [
        self::STATE_PENDING,
        self::STATE_USED,
    ];

    /**
     * @var string[]
     */
    protected $casts = [
        'validated_at' => 'datetime',
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
        'uid', 'identity_address', 'redeemed_by_address', 'state', 'fund_id', 'organization_id', 'employee_id',
        'exported', 'json', 'records_hash', 'uid_hash', 'validated_at',
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
     * @return int|null
     */
    public static function assignAvailableToIdentityByBsn(Identity $identity): ?int
    {
        if (!$identity->bsn) {
            return null;
        }

        $query = static::where([
            'state' => static::STATE_PENDING,
        ])->whereRelation('fund.organization', [
            'bsn_enabled' => true,
        ]);

        /** @var Prevalidation[]|\Illuminate\Database\Eloquent\Collection $prevalidations */
        $prevalidations = $query->where(function (Builder $query) use ($identity) {
            // Where BSN record match
            $query->whereHas('prevalidation_records', fn (Builder $builder) => $builder->where([
                'value' => $identity->bsn,
            ])->whereRelation('record_type', 'key', '=', 'bsn'));
        })->get();

        return $prevalidations->each(static function (Prevalidation $prevalidation) use ($identity) {
            $prevalidation->assignToIdentity($identity);
        })->count();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function prevalidation_records(): HasMany
    {
        return $this->hasMany(PrevalidationRecord::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function records(): HasMany
    {
        return $this->hasMany(PrevalidationRecord::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function fund(): BelongsTo
    {
        return $this->belongsTo(Fund::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @param Builder|Relation|Prevalidation $builder
     * @return Collection
     */
    public static function export(Builder|Relation|Prevalidation $builder): Collection
    {
        $transKey = 'export.prevalidations';

        $builder->with([
            'prevalidation_records.record_type.translations',
        ]);

        $data = $builder->get()->map(static function (Prevalidation $prevalidation) use ($transKey) {
            return collect([
                trans("$transKey.code") => $prevalidation->uid,
                trans("$transKey.used") => trans("$transKey.used_" . ($prevalidation->state === self::STATE_USED ? 'yes' : 'no')),
            ])->merge($prevalidation->prevalidation_records->filter(function (PrevalidationRecord $record) {
                return !str_contains($record->record_type->key, '_eligible');
            })->pluck('value', 'record_type.name'))->toArray();
        })->values();

        (clone $builder)->update([
            'exported' => true,
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
                ->approve(null, $this->organization, $this);
        }

        return tap($this)->update([
            'state' => 'used',
            'redeemed_by_address' => $identity->address,
        ]);
    }

    /**
     * @param Employee $employee
     * @param Fund $fund
     * @param array $data
     * @param array $overwriteKeys
     * @return EloquentCollection
     */
    public static function storePrevalidations(
        Employee $employee,
        Fund $fund,
        array $data,
        array $overwriteKeys = []
    ): EloquentCollection {
        $primaryKeyName = $fund->fund_config->csv_primary_key;

        $recordTypes = array_pluck(record_types_cached(), 'id', 'key');
        $fundPrevalidationPrimaryKey = $recordTypes[$primaryKeyName] ??
            abort(500, 'Unknown csv_primary_key');

        // list existing uid from fund prevalidations
        $existingPrimaryKeys = PrevalidationRecord::whereHas('prevalidation', fn (Builder $builder) => $builder->where([
            'identity_address' => $employee->identity_address,
            'organization_id' => $fund->organization_id,
            'employee_id' => $employee->id,
            'fund_id' => $fund->id,
        ]))->where([
            'record_type_id' => $fundPrevalidationPrimaryKey,
        ])->pluck('value');

        // only new pre validations and pre validations that have to be updated
        $data = array_map(static function ($record) use ($existingPrimaryKeys, $fund, $overwriteKeys, $recordTypes) {
            $primaryKey = $record[$fund->fund_config->csv_primary_key];

            // already exists and not in the replace list
            if ($existingPrimaryKeys->search($primaryKey) !== false &&
                !in_array($primaryKey, $overwriteKeys, true)) {
                return null;
            }

            return [
                'primaryKey' => $primaryKey,
                'record' => $record,
                'records' => array_filter(array_map(static function ($key) use ($recordTypes, $record) {
                    return (!$recordTypes[$key] || $key === 'primary_email') ? false : [
                        'record_type_id' => $recordTypes[$key],
                        'value' => is_null($record[$key]) ? '' : $record[$key],
                    ];
                }, array_keys($record)), static function ($value) {
                    return (bool) $value;
                }),
            ];
        }, $data);

        // filter null rows
        $data = array_filter($data, fn ($item) => is_array($item));

        $prevalidations = array_map(static function (array $records) use ($fund, $overwriteKeys, $fundPrevalidationPrimaryKey, $employee) {
            if (in_array($records['primaryKey'], $overwriteKeys, true)) {
                // find existing prevalidation to be updated
                $prevalidation = Prevalidation::where([
                    'state' => Prevalidation::STATE_PENDING,
                    'identity_address' => $employee->identity_address,
                    'organization_id' => $fund->organization_id,
                    'employee_id' => $employee->id,
                    'fund_id' => $fund->id,
                ])->whereRelation('prevalidation_records', [
                    'record_type_id' => $fundPrevalidationPrimaryKey,
                    'value' => $records['primaryKey'],
                ])->first();

                $prevalidation->prevalidation_records()->delete();
            } else {
                // make a new prevalidation
                $prevalidation = Prevalidation::create([
                    'uid' => self::makeNewUid(),
                    'state' => Prevalidation::STATE_PENDING,
                    'validated_at' => now(),
                    'identity_address' => $employee->identity_address,
                    'organization_id' => $fund->organization_id,
                    'employee_id' => $employee->id,
                    'fund_id' => $fund->id,
                ]);
            }

            // save records
            $prevalidation->prevalidation_records()->createMany($records['records']);

            return $prevalidation->updateHashes();
        }, $data);

        return new EloquentCollection($prevalidations);
    }

    /**
     * @return $this
     */
    public function updateHashes(): Prevalidation
    {
        $records = $this->prevalidation_records->pluck(
            'value',
            'record_type.key'
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
    public function getIsUsedAttribute(): bool
    {
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
