<?php

namespace App\Models;

use App\Events\FundRequests\FundRequestResolved;
use App\Scopes\Builders\FundRequestRecordQuery;
use App\Services\EventLogService\Traits\HasLogs;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Http\Request;

/**
 * App\Models\FundRequest
 *
 * @property int $id
 * @property int $fund_id
 * @property string $identity_address
 * @property string $note
 * @property string $disregard_note
 * @property bool $disregard_notify
 * @property string|null $state
 * @property \Illuminate\Support\Carbon|null $resolved_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Collection|\App\Models\FundRequestClarification[] $clarifications
 * @property-read int|null $clarifications_count
 * @property-read \App\Models\Fund $fund
 * @property-read int|null $lead_time_days
 * @property-read string $lead_time_locale
 * @property-read Collection|\App\Services\EventLogService\Models\EventLog[] $logs
 * @property-read int|null $logs_count
 * @property-read Collection|\App\Models\FundRequestRecord[] $records
 * @property-read int|null $records_count
 * @property-read Collection|\App\Models\FundRequestRecord[] $records_approved
 * @property-read int|null $records_approved_count
 * @property-read Collection|\App\Models\FundRequestRecord[] $records_declined
 * @property-read int|null $records_declined_count
 * @property-read Collection|\App\Models\FundRequestRecord[] $records_disregarded
 * @property-read int|null $records_disregarded_count
 * @property-read Collection|\App\Models\FundRequestRecord[] $records_pending
 * @property-read int|null $records_pending_count
 * @method static Builder|FundRequest newModelQuery()
 * @method static Builder|FundRequest newQuery()
 * @method static Builder|FundRequest query()
 * @method static Builder|FundRequest whereCreatedAt($value)
 * @method static Builder|FundRequest whereDisregardNote($value)
 * @method static Builder|FundRequest whereDisregardNotify($value)
 * @method static Builder|FundRequest whereFundId($value)
 * @method static Builder|FundRequest whereId($value)
 * @method static Builder|FundRequest whereIdentityAddress($value)
 * @method static Builder|FundRequest whereNote($value)
 * @method static Builder|FundRequest whereResolvedAt($value)
 * @method static Builder|FundRequest whereState($value)
 * @method static Builder|FundRequest whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class FundRequest extends Model
{
    use HasLogs;

    public const EVENT_CREATED = 'created';
    public const EVENT_APPROVED = 'approved';
    public const EVENT_DECLINED = 'declined';
    public const EVENT_DISREGARDED = 'disregarded';
    public const EVENT_APPROVED_PARTLY = 'approved_partly';
    public const EVENT_RESOLVED = 'resolved';

    public const EVENT_RECORD_DECLINED = 'record_declined';
    public const EVENT_CLARIFICATION_REQUESTED = 'clarification_requested';

    public const STATE_PENDING = 'pending';
    public const STATE_APPROVED = 'approved';
    public const STATE_DECLINED = 'declined';
    public const STATE_DISREGARDED = 'disregarded';
    public const STATE_APPROVED_PARTLY = 'approved_partly';

    public const EVENTS = [
        self::EVENT_CREATED,
        self::EVENT_APPROVED,
        self::EVENT_DECLINED,
        self::EVENT_DISREGARDED,
        self::EVENT_APPROVED_PARTLY,
        self::EVENT_RESOLVED,
    ];

    public const STATES = [
        self::STATE_PENDING,
        self::STATE_APPROVED,
        self::STATE_DECLINED,
        self::STATE_APPROVED_PARTLY,
        self::STATE_DISREGARDED,
    ];

    public const STATES_RESOLVED = [
        self::STATE_APPROVED,
        self::STATE_DECLINED,
        self::STATE_APPROVED_PARTLY,
        self::STATE_DISREGARDED,
    ];

    protected $fillable = [
        'fund_id', 'identity_address', 'employee_id', 'note', 'state', 'resolved_at',
        'disregard_note', 'disregard_notify',
    ];

    protected $dates = [
        'resolved_at'
    ];

    protected $casts = [
        'disregard_notify' => 'boolean',
    ];

    /**
     * @return int|null
     * @noinspection PhpUnused
     */
    public function getLeadTimeDaysAttribute(): ?int
    {
        return ($this->resolved_at ?: now())->diffInDays($this->created_at);
    }

    /**
     * @return string
     * @noinspection PhpUnused
     */
    public function getLeadTimeLocaleAttribute(): string
    {
        return ($this->resolved_at ?: now())->longAbsoluteDiffForHumans($this->created_at);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param Organization $organization
     * @param string $identity_address
     * @return FundRequest|\Illuminate\Database\Eloquent\Builder
     */
    public static function search(
        Request $request,
        Organization $organization,
        string $identity_address
    ) {
        /** @var Builder $query */
        $query = self::query();
        $recordRepo = resolve('forus.services.record');

        $query->whereHas('records', static function(
            Builder $builder
        ) use ($organization, $identity_address) {
            FundRequestRecordQuery::whereIdentityCanBeValidatorFilter(
                $builder,
                $identity_address,
                $organization->findEmployee($identity_address)->id,
                ['manage_validators']
            );
        });

        if ($request->has('q') && $q = $request->input('q')) {
            $query->where(function (Builder $query) use ($q, $recordRepo) {
                $query->whereHas('fund', static function(Builder $builder) use ($q) {
                    $builder->where('name', 'LIKE', "%$q%");
                });

                if ($bsn_identity_address = $recordRepo->identityAddressByBsn($q)) {
                    $query->orWhere('identity_address', '=', $bsn_identity_address);
                }
            });
        }

        if ($request->has('state') && $state = $request->input('state')) {
            $query->where('state', $state);
        }

        if ($request->has('from') && $from = $request->input('from')) {
            $query->where('created_at', '>=', $from);
        }

        if ($request->has('to') && $to = $request->input('to')) {
            $query->where('created_at', '<=', $to);
        }

        if ($request->has('employee_id') && $employee_id = $request->input('employee_id')) {
            $employee = Employee::find($employee_id);

            $query->whereHas('records', static function(Builder $builder) use ($employee) {
                FundRequestRecordQuery::whereIdentityIsAssignedEmployeeFilter(
                    $builder, $employee->identity_address, $employee->id
                );
            });
        }

        return $query->orderBy(
            $request->get('sort_by', 'created_at'),
            $request->get('sort_order', 'DESC')
        )->orderBy('created_at');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function fund(): BelongsTo
    {
        return $this->belongsTo(Fund::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function records(): HasMany
    {
        return $this->hasMany(FundRequestRecord::class);
    }

    /**
     * @param $identity_address
     * @param $employee_id
     * @return Builder
     */
    public function recordsWhereCanValidateQuery($identity_address, $employee_id): Builder
    {
        return FundRequestRecordQuery::whereIdentityCanBeValidatorFilter(
            self::records()->getQuery(),
            $identity_address,
            $employee_id
        );
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function clarifications(): HasManyThrough
    {
        return $this->hasManyThrough(
            FundRequestClarification::class,
            FundRequestRecord::class
        );
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function records_approved(): HasMany
    {
        return $this->records()->where([
            'fund_request_records.state' => FundRequestRecord::STATE_APPROVED
        ]);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     * @noinspection PhpUnused
     */
    public function records_declined(): HasMany
    {
        return $this->hasMany(FundRequestRecord::class)->where([
            'fund_request_records.state' => FundRequestRecord::STATE_DECLINED
        ]);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     * @noinspection PhpUnused
     */
    public function records_pending(): HasMany
    {
        return $this->hasMany(FundRequestRecord::class)->where([
            'fund_request_records.state' => FundRequestRecord::STATE_PENDING
        ]);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function records_disregarded(): HasMany
    {
        return $this->records()->where([
            'fund_request_records.state' => FundRequestRecord::STATE_DISREGARDED
        ]);
    }

    /**
     * Set all fund request records assigned to given employee as declined
     * @param Employee $employee
     * @param string|null $note
     * @return FundRequest
     * @throws \Exception
     */
    public function decline(Employee $employee, ?string $note = null): self
    {
        $this->update([
            'note' => $note ?: ''
        ]);

        $this->checkPartnerBsnRecord($employee, $note);

        $this->records_pending()->where([
            'employee_id' => $employee->id
        ])->each(static function(FundRequestRecord $record) use ($note) {
            $record->decline($note);
        });

        return $this;
    }

    /**
     * @param Employee $employee
     * @param string|null $note
     * @throws \Exception
     */
    private function checkPartnerBsnRecord(Employee $employee, ?string $note = null): void
    {
        /** @var FundRequestRecord $record_partner_bsn */
        $record_partner_bsn = $this->records()->where([
            'employee_id' => $employee->id,
            'record_type_key' => 'partner_bsn'
        ])->first();

        $decline = $record_partner_bsn && !$this->records_approved()
                ->whereNotIn('fund_request_records.id', [$record_partner_bsn->id])
                ->count();

        if ($decline) {
            $record_partner_bsn->decline($note);
        }
    }

    /**
     * Set all fund request records assigned to given employee as approved
     *
     * @param Employee $employee
     * @return $this
     */
    public function approve(Employee $employee): self
    {
        $this->records_pending()->where([
            'employee_id' => $employee->id
        ])->each(static function(FundRequestRecord $record) {
            $record->approve();
        });

        return $this;
    }

    /**
     * Set all fund request pending records assigned to given employee as disregarded
     *
     * @param Employee $employee
     * @param string|null $note
     * @param bool $notify
     * @return FundRequest
     */
    public function disregard(Employee $employee, ?string $note = null, bool $notify = false): self
    {
        $this->update([
            'disregard_note' => $note ?: '',
            'disregard_notify' => $notify ?: '',
        ]);

        $this->records_pending()->where([
            'employee_id' => $employee->id
        ])->each(static function(FundRequestRecord $record) use ($note) {
            $record->disregard($note);
        });

        return $this;
    }

    /**
     * Set all disregarded fund request records assigned to given employee as pending
     * @param Employee $employee
     * @return FundRequest
     */
    public function disregardUndo(Employee $employee): self
    {
        $this->records_disregarded()->where([
            'employee_id' => $employee->id
        ])->each(static function(FundRequestRecord $record) {
            $record->disregardUndo();
        });

        if ($this->records_pending()->exists()) {
            $this->updateStateByRecords();
        }

        return $this;
    }

    /**
     * @param string $state
     * @param array $data
     * @return $this
     */
    protected function updateState(string $state, array $data = []): self
    {
        return $this->updateModel(array_merge(compact('state'), $data));
    }

    /**
     * Resolve fund request by applying validations to requester
     * from all approved fund request records and changes the status of
     * the request accordingly
     * @return $this
     */
    public function resolve(): self
    {
        $records = $this->records()->whereHas('employee');

        if ((clone $records)->where('state', '=', self::STATE_DISREGARDED)->doesntExist()) {
            $records->where('state', '=', self::STATE_APPROVED);

            $records->get()->each(static function(FundRequestRecord $record) {
                $record->makeValidation();
            });
        }

        $this->updateStateByRecords();

        return $this;
    }

    /**
     * @return $this
     */
    public function updateStateByRecords(): FundRequest
    {
        $countAll = $this->records()->count();
        $countPending = $this->records_pending()->count();
        $countApproved = $this->records_approved()->count();
        $countDisregarded = $this->records_disregarded()->count();
        $oldState = $this->state;

        if ($countPending > 0) {
            if ($this->state !== self::STATE_PENDING) {
                $this->updateState(self::STATE_PENDING);
            }

            return $this;
        }

        if ($countApproved === $countAll) {
            $state = self::STATE_APPROVED;
        } elseif ($countDisregarded > 0) {
            $state = self::STATE_DISREGARDED;
        } else {
            $state = $countApproved > 0 ? self::STATE_APPROVED_PARTLY : self::STATE_DECLINED;
        }

        $this->updateState($state, in_array($state, static::STATES_RESOLVED) ? [
            'resolved_at' => now(),
        ] : []);

        if (!$this->isPending() && ($oldState !== $this->state)) {
            FundRequestResolved::dispatch($this);
        }

        return $this;
    }

    /**
     * Assign all available pending fund request records to given employee
     * @param Employee $employee
     * @return $this
     */
    public function assignEmployee(Employee $employee): self
    {
        FundRequestRecordQuery::whereIdentityCanBeValidatorFilter(
            $this->records()->where([
                'state' => FundRequestRecord::STATE_PENDING,
            ])->getQuery(),
            $employee->identity_address,
            $employee->id
        )->whereDoesntHave('employee')->update([
            'employee_id' => $employee->id
        ]);

        return $this;
    }

    /**
     * Remove all assigned fund request records from employee
     * @param Employee $employee
     * @param FundCriterion|null $fundCriterion
     * @return $this
     */
    public function resignEmployee(
        Employee $employee,
        ?FundCriterion $fundCriterion = null
    ): self {
        $this->records()->where([
            'employee_id' => $employee->id,
            'record_type_key' => 'partner_bsn'
        ])->forceDelete();

        $query = $this->records()->where([
            'employee_id' => $employee->id,
        ]);

        if (!is_null($fundCriterion)) {
            $query->where('fund_criterion_id', $fundCriterion->id);
        }

        $query->update([
            'employee_id' => null,
            'state' => FundRequestRecord::STATE_PENDING,
        ]);

        if ($this->state === self::STATE_APPROVED_PARTLY && $this->records_pending()->exists()) {
            $this->update([
                'state' => self::STATE_PENDING
            ]);
        }

        return $this;
    }

    /**
     * Prepare fund requests for exporting
     * @param Builder $builder
     * @return Builder[]|Collection|\Illuminate\Support\Collection
     */
    private static function exportTransform(Builder $builder)
    {
        $transKey = "export.fund_requests";
        $recordRepo = resolve('forus.services.record');
        $fundRequests = $builder->with('records.employee', 'fund')->get();

        return $fundRequests->map(static function(FundRequest $fundRequest) use ($transKey, $recordRepo) {
            return [
                trans("$transKey.bsn") => $recordRepo->bsnByAddress($fundRequest->identity_address),
                trans("$transKey.fund_name") => $fundRequest->fund->name,
                trans("$transKey.status") => trans("$transKey.state-values.$fundRequest->state"),
                trans("$transKey.validator") => $fundRequest->records->filter()->pluck('employee')->count() > 0 ?
                    $fundRequest->records->pluck('employee')->filter()->map(static function(
                        Employee $employee
                    ) use ($recordRepo) {
                        return $recordRepo->primaryEmailByAddress($employee->identity_address);
                    })->unique()->join(', ') : null,
                trans("$transKey.created_at") => $fundRequest->created_at,
                trans("$transKey.resolved_at") => $fundRequest->resolved_at,
                trans("$transKey.lead_time_days") => (string) $fundRequest->lead_time_days,
                trans("$transKey.lead_time_locale") => $fundRequest->lead_time_locale,
            ];
        })->values();
    }

    /**
     * Export fund requests
     * @param Request $request
     * @param Organization $organization
     * @param string $identity_address
     * @return Builder[]|Collection|\Illuminate\Support\Collection
     */
    public static function exportSponsor(
        Request $request,
        Organization $organization,
        string $identity_address
    ) {
        return self::exportTransform(self::search($request, $organization, $identity_address));
    }

    /**
     * @return bool
     */
    public function isPending(): bool
    {
        return $this->state === self::STATE_PENDING;
    }

    /**
     * @return bool
     */
    public function isDisregarded(): bool
    {
        return $this->state === self::STATE_DISREGARDED;
    }

    /**
     * @return bool
     */
    public function isApproved(): bool
    {
        return $this->state === self::STATE_APPROVED;
    }

    /**
     * @return bool
     */
    public function isDeclined(): bool
    {
        return $this->state === self::STATE_DECLINED;
    }

    /**
     * @return bool
     */
    public function isResolved(): bool
    {
        return in_array($this->state, self::STATES_RESOLVED);
    }
}
