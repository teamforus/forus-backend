<?php

namespace App\Models;

use App\Events\FundRequests\FundRequestAssigned;
use App\Events\FundRequests\FundRequestResigned;
use App\Events\FundRequestRecords\FundRequestRecordAssigned;
use App\Events\FundRequestRecords\FundRequestRecordResigned;
use App\Events\FundRequests\FundRequestResolved;
use App\Models\Traits\HasNotes;
use App\Http\Requests\Api\Platform\Funds\Requests\IndexFundRequestsRequest;
use App\Scopes\Builders\FundRequestRecordQuery;
use App\Searches\FundRequestSearch;
use App\Services\EventLogService\Traits\HasLogs;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

/**
 * App\Models\FundRequest
 *
 * @property int $id
 * @property int $fund_id
 * @property string $identity_address
 * @property string|null $contact_information
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
 * @property-read string $state_locale
 * @property-read \App\Models\Identity $identity
 * @property-read Collection|\App\Services\EventLogService\Models\EventLog[] $logs
 * @property-read int|null $logs_count
 * @property-read Collection|\App\Models\Note[] $notes
 * @property-read int|null $notes_count
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
 * @method static Builder|FundRequest whereContactInformation($value)
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
class FundRequest extends BaseModel
{
    use HasLogs, HasNotes;

    public const EVENT_CREATED = 'created';
    public const EVENT_APPROVED = 'approved';
    public const EVENT_DECLINED = 'declined';
    public const EVENT_APPROVED_PARTLY = 'approved_partly';
    public const EVENT_RESOLVED = 'resolved';
    public const EVENT_ASSIGNED = 'assigned';
    public const EVENT_RESIGNED = 'resigned';
    public const EVENT_DISREGARDED = 'disregarded';

    public const STATE_PENDING = 'pending';
    public const STATE_APPROVED = 'approved';
    public const STATE_DECLINED = 'declined';
    public const STATE_DISREGARDED = 'disregarded';
    public const STATE_APPROVED_PARTLY = 'approved_partly';

    public const EVENTS = [
        self::EVENT_CREATED,
        self::EVENT_APPROVED,
        self::EVENT_DECLINED,
        self::EVENT_APPROVED_PARTLY,
        self::EVENT_RESOLVED,
        self::EVENT_ASSIGNED,
        self::EVENT_RESIGNED,
    ];

    public const STATES = [
        self::STATE_PENDING,
        self::STATE_APPROVED,
        self::STATE_DECLINED,
        self::STATE_DISREGARDED,
        self::STATE_APPROVED_PARTLY,
    ];

    public const STATES_RESOLVED = [
        self::STATE_APPROVED,
        self::STATE_DECLINED,
        self::STATE_APPROVED_PARTLY,
        self::STATE_DISREGARDED,
    ];

    protected $fillable = [
        'fund_id', 'identity_address', 'employee_id', 'note', 'state', 'resolved_at',
        'disregard_note', 'disregard_notify', 'identity_address',
        'contact_information',
    ];

    protected $dates = [
        'resolved_at',
    ];

    protected $casts = [
        'disregard_notify' => 'boolean',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function identity(): BelongsTo
    {
        return $this->belongsTo(Identity::class, 'identity_address', 'address');
    }

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
     * @return string
     * @noinspection PhpUnused
     */
    public function getStateLocaleAttribute(): string
    {
        return [
            self::STATE_PENDING => 'Wachten',
            self::STATE_APPROVED => 'Geaccepteerd',
            self::STATE_APPROVED_PARTLY => 'Aanvulling gevraagd',
            self::STATE_DECLINED => 'Geweigerd',
            self::STATE_DISREGARDED => 'Niet beoordeeld',
        ][$this->state] ?? '';
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
            $record->decline($note, false);
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

        $decline = $record_partner_bsn && $this->records_approved()
            ->where('fund_request_records.id', '!=', $record_partner_bsn->id)
            ->doesntExist();

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
     * @param Employee|null $supervisorEmployee
     * @return $this
     */
    public function assignEmployee(Employee $employee, ?Employee $supervisorEmployee = null): self
    {
        /** @var FundRequestRecord[] $records */
        $records = FundRequestRecordQuery::whereEmployeeCanBeValidator(
            $this->records_pending()->whereDoesntHave('employee'),
            $employee
        )->get();

        foreach ($records as $record) {
            FundRequestRecordAssigned::dispatch($record->updateModel([
                'employee_id' => $employee->id,
            ]), $employee, $supervisorEmployee);
        }

        FundRequestAssigned::dispatch($this, $employee, $supervisorEmployee);

        return $this;
    }

    /**
     * Remove all assigned fund request records from employee
     * @param Employee $employee
     * @param FundCriterion|null $fundCriterion
     * @param Employee|null $supervisorEmployee
     * @return $this
     */
    public function resignEmployee(
        Employee $employee,
        ?FundCriterion $fundCriterion = null,
        ?Employee $supervisorEmployee = null
    ): self {
        $this->records()->where([
            'employee_id' => $employee->id,
            'record_type_key' => 'partner_bsn'
        ])->forceDelete();

        $query = $this->records()->where('employee_id', $employee->id);

        if (!is_null($fundCriterion)) {
            $query->where('fund_criterion_id', $fundCriterion->id);
        }

        /** @var FundRequestRecord $record */
        foreach ($query->get() as $record) {
            FundRequestRecordResigned::dispatch($record->updateModel([
                'employee_id' => null,
                'state' => FundRequestRecord::STATE_PENDING,
            ]), $employee, $supervisorEmployee);
        }

        if ($this->state === self::STATE_APPROVED_PARTLY && $this->records_pending()->exists()) {
            $this->update([
                'state' => self::STATE_PENDING
            ]);
        }

        FundRequestResigned::dispatch($this, $employee, $supervisorEmployee);

        return $this;
    }

    /**
     * @param Organization $organization
     * @param Employee|null $supervisorEmployee
     * @return $this
     */
    public function resignAllEmployees(
        Organization $organization,
        ?Employee $supervisorEmployee
    ): self {
        /** @var Employee[] $employees */
        $employees = $organization->employees()->where(function(Builder $builder) {
            $builder->whereHas('fund_request_records', function(Builder $builder) {
                $builder->where('fund_request_records.fund_request_id', $this->id);
            });
        })->get();

        foreach ($employees as $employee) {
            $this->resignEmployee($employee, null, $supervisorEmployee);
        }

        return $this;
    }

    /**
     * Prepare fund requests for exporting
     *
     * @param Builder $builder
     * @return Builder[]|Collection|\Illuminate\Support\Collection
     */
    private static function exportTransform(Builder $builder): mixed
    {
        $fundRequests = (clone $builder)->with([
            'identity.record_bsn',
            'records.employee',
            'fund',
        ])->get();

        $recordKeyList = FundRequestRecord::query()
            ->whereIn('fund_request_id', (clone $builder)->select('id'))
            ->pluck('record_type_key');

        return $fundRequests->map(static function(FundRequest $request) use ($recordKeyList) {
            $employees = $request->records->pluck('employee')->filter();
            $employees = $employees->map(fn(Employee $employee) => $employee->identity->email)->unique();

            $records = $recordKeyList->reduce(fn ($records, $key) => [
                ...$records, $key => $request->records->firstWhere('record_type_key', $key),
            ], []);

            return array_merge([
                trans("export.fund_requests.bsn") => $request->identity?->record_bsn?->value ?: '-',
                trans("export.fund_requests.fund_name") => $request->fund->name,
                trans("export.fund_requests.status") => trans("export.fund_requests.state-values.$request->state"),
                trans("export.fund_requests.validator") => $employees->filter()->join(', ') ?: null,
                trans("export.fund_requests.created_at") => $request->created_at,
                trans("export.fund_requests.resolved_at") => $request->resolved_at,
                trans("export.fund_requests.lead_time_days") => (string) $request->lead_time_days,
                trans("export.fund_requests.lead_time_locale") => $request->lead_time_locale,
            ], array_map(fn(?FundRequestRecord $record = null) => $record?->value ?: '-', $records));
        })->values();
    }

    /**
     * Export fund requests
     * @param IndexFundRequestsRequest $request
     * @param Employee $employee
     * @return Builder[]|Collection|\Illuminate\Support\Collection
     */
    public static function exportSponsor(IndexFundRequestsRequest $request, Employee $employee): mixed
    {
        $search = (new FundRequestSearch($request->only([
            'q', 'state', 'employee_id', 'from', 'to', 'order_by', 'order_dir', 'assigned',
        ])))->setEmployee($employee);

        return self::exportTransform($search->query());
    }

    /**
     * @return bool
     * @noinspection PhpUnused
     */
    public function isPending(): bool
    {
        return $this->state === self::STATE_PENDING;
    }

    /**
     * @return bool
     * @noinspection PhpUnused
     */
    public function isDisregarded(): bool
    {
        return $this->state === self::STATE_DISREGARDED;
    }

    /**
     * @return bool
     * @noinspection PhpUnused
     */
    public function isApproved(): bool
    {
        return $this->state === self::STATE_APPROVED;
    }

    /**
     * @return bool
     * @noinspection PhpUnused
     */
    public function isDeclined(): bool
    {
        return $this->state === self::STATE_DECLINED;
    }

    /**
     * @return bool
     * @noinspection PhpUnused
     */
    public function isResolved(): bool
    {
        return in_array($this->state, self::STATES_RESOLVED);
    }
}
