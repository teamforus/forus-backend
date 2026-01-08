<?php

namespace App\Models;

use App\Models\Traits\HasDbTokens;
use App\Scopes\Builders\PrevalidationQuery;
use App\Scopes\Builders\VoucherQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use RuntimeException;

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
 * @property int|null $prevalidation_request_id
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
 * @property-read \App\Models\Identity|null $identity_redeemed
 * @property-read \App\Models\Organization|null $organization
 * @property-read EloquentCollection|\App\Models\PrevalidationRecord[] $prevalidation_records
 * @property-read int|null $prevalidation_records_count
 * @property-read \App\Models\PrevalidationRequest|null $prevalidation_request
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
 * @method static Builder<static>|Prevalidation wherePrevalidationRequestId($value)
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
class Prevalidation extends BaseModel
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
        'exported', 'json', 'records_hash', 'uid_hash', 'validated_at', 'prevalidation_request_id',
    ];

    /**
     * @return BelongsTo
     */
    public function identity(): BelongsTo
    {
        return $this->belongsTo(Identity::class, 'identity_address', 'address');
    }

    /**
     * @return BelongsTo
     */
    public function identity_redeemed(): BelongsTo
    {
        return $this->belongsTo(Identity::class, 'redeemed_by_address', 'address');
    }

    /**
     * @noinspection PhpUnused
     * @return BelongsTo
     */
    public function prevalidation_request(): BelongsTo
    {
        return $this->belongsTo(PrevalidationRequest::class);
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

        return $this->updateModel([
            'state' => 'used',
            'redeemed_by_address' => $identity->address,
        ]);
    }

    /**
     * @param Employee $employee
     * @param Fund $fund
     * @param array $data
     * @param array $topUps
     * @param array $overwriteKeys
     * @return EloquentCollection|Prevalidation[]
     */
    public static function storePrevalidations(
        Employee $employee,
        Fund $fund,
        array $data,
        array $topUps,
        array $overwriteKeys,
    ): EloquentCollection|array {
        $primaryKeyName = $fund->fund_config->csv_primary_key;
        $topUpKeys = array_pluck($topUps, 'key');
        $updateKeys = [...$topUpKeys, ...$overwriteKeys];

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
        $data = array_reduce($data, function (array $list, array $record) use ($existingPrimaryKeys, $fund, $updateKeys, $recordTypes) {
            $primaryKey = $record[$fund->fund_config->csv_primary_key];

            // already exists and not in the replacement list
            if ($existingPrimaryKeys->search($primaryKey) !== false && !in_array($primaryKey, $updateKeys, true)) {
                return $list;
            }

            return [...$list, [
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
            ]];
        }, []);

        $prevalidations = array_map(static function (array $records) use ($fund, $updateKeys, $topUpKeys, $topUps, $fundPrevalidationPrimaryKey, $employee) {
            if (in_array($records['primaryKey'], $updateKeys, true)) {
                // find existing prevalidation to be updated
                $prevalidation = Prevalidation::where([
                    'state' => in_array($records['primaryKey'], $topUpKeys) ? Prevalidation::STATE_USED : Prevalidation::STATE_PENDING,
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
            $prevalidation->updateHashes();

            if (in_array($records['primaryKey'], $topUpKeys, true)) {
                $voucherId = array_first(array_where($topUps, function (array $topUp) use ($records) {
                    return $topUp['key'] === $records['primaryKey'];
                }))['voucher_id'] ?? null;

                if (!$prevalidation->makeVoucherTopUp($employee, $fund, $records['primaryKey'], $voucherId)) {
                    throw new RuntimeException('Kan geen opwaardeertransactie uitvoeren voor ' . $records['primaryKey']);
                }
            }

            return $prevalidation;
        }, $data);

        return new EloquentCollection($prevalidations);
    }

    /**
     * @param Employee $employee
     * @param Fund $fund
     * @param string $primaryKey
     * @param string|int $voucherId
     * @return bool
     */
    public function makeVoucherTopUp(Employee $employee, Fund $fund, string $primaryKey, string|int $voucherId): bool
    {
        $voucher = $this->findVoucherForTopUp($employee, $fund, $primaryKey, $voucherId);
        $diff = $voucher ? $this->calcVoucherTopUpAmount($fund, $voucher) : null;

        if ($voucher && $diff > 0) {
            $voucher->makeSponsorTopUpTransaction($employee, $diff, 'Top up-transactie vanwege gewijzigde gegevens.');

            return true;
        }

        return false;
    }

    /**
     * @param Organization $organization
     * @param Fund $fund
     * @param Employee $employee
     * @param string|array|null $uid
     * @return array
     */
    public static function getDbState(
        Organization $organization,
        Fund $fund,
        Employee $employee,
        null|string|array $uid = null,
    ): array {
        $query = PrevalidationQuery::whereVisibleToIdentity(
            $organization->prevalidations(),
            $employee->identity_address,
        );

        $prevalidations = $query
            ->where(
                fn ($query) => $uid
                ? $query->whereHas('records', fn (Builder|PrevalidationRecord $q) => $q
                    ->whereRelation('record_type', 'key', $fund->fund_config->csv_primary_key)
                    ->whereIn('value', (array) $uid))
                : $query
            )
            ->where('fund_id', $fund->id)
            ->where('employee_id', $employee->id)
            ->whereIn('state', [Prevalidation::STATE_PENDING, Prevalidation::STATE_USED])
            ->with(['identity_redeemed'])
            ->get();

        return $prevalidations->reduce(function (array $list, Prevalidation $prevalidation) use ($fund) {
            $vouchers = $prevalidation->identity_redeemed
                ? VoucherQuery::whereActive(Voucher::query())
                    ->where('identity_id', '=', $prevalidation->identity_redeemed->id)
                    ->where('fund_id', $fund->id)
                    ->where('voucher_type', Voucher::VOUCHER_TYPE_VOUCHER)
                    ->whereNull('product_id')
                    ->get()
                    ->map(fn ($voucher) => [
                        'id' => $voucher->id,
                        'amount' => $voucher->amount_total,
                    ])->toArray()
                : [];

            return [...$list, [
                ...$prevalidation->only([
                    'id', 'state', 'uid_hash', 'records_hash',
                ]),
                'vouchers' => $vouchers,
            ]];
        }, []);
    }

    /**
     * @param Fund $fund
     * @param array $data
     * @return array
     */
    public static function getCollectionState(Fund $fund, array $data): array
    {
        return array_map(static function ($row) use ($fund) {
            ksort($row);

            return [
                'data' => $row,
                'uid_hash' => hash('sha256', $row[$fund->fund_config->csv_primary_key]),
                'records_hash' => hash('sha256', json_encode($row)),
                'records_amount' => currency_format($fund->amountForIdentity(null, records: $row)),
            ];
        }, $data);
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

    /**
     * @param Employee $employee
     * @param Fund $fund
     * @param string $primaryKey
     * @param string|int $voucherId
     * @return Voucher|null
     */
    protected function findVoucherForTopUp(Employee $employee, Fund $fund, string $primaryKey, string|int $voucherId): ?Voucher
    {
        $state = self::getDbState($employee->organization, $fund, $employee, $primaryKey);
        $vouchers = array_get($state, '0.vouchers');
        $targetVoucherId = array_get($state, '0.vouchers.0.id');

        if (count($state) == 1 && count($vouchers) == 1 && $targetVoucherId == $voucherId) {
            return $fund->vouchers()->find($targetVoucherId);
        }

        return null;
    }

    /**
     * @param Fund $fund
     * @param Voucher $voucher
     * @return Voucher|null
     */
    protected function calcVoucherTopUpAmount(Fund $fund, Voucher $voucher): ?string
    {
        $records = $this->records()->get()
            ->mapWithKeys(fn (PrevalidationRecord $record) => [$record->record_type->key => $record->value])
            ->toArray();

        $amount = $fund->amountForIdentity(identity: null, records: $records);

        return currency_format($amount - $voucher->amount_total);
    }
}
