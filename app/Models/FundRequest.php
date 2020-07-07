<?php

namespace App\Models;

use App\Events\FundRequests\FundRequestResolved;
use App\Scopes\Builders\FundQuery;
use App\Scopes\Builders\FundRequestRecordQuery;
use App\Scopes\Builders\OrganizationQuery;
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
 * @property string $state
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\FundRequestClarification[] $clarifications
 * @property-read int|null $clarifications_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\FundRequestRecord[] $clarifications_answered
 * @property-read int|null $clarifications_answered_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\FundRequestRecord[] $clarifications_pending
 * @property-read int|null $clarifications_pending_count
 * @property-read \App\Models\Fund $fund
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\FundRequestRecord[] $records
 * @property-read int|null $records_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\FundRequestRecord[] $records_approved
 * @property-read int|null $records_approved_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\FundRequestRecord[] $records_declined
 * @property-read int|null $records_declined_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\FundRequestRecord[] $records_pending
 * @property-read int|null $records_pending_count
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundRequest newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundRequest newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundRequest query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundRequest whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundRequest whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundRequest whereFundId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundRequest whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundRequest whereIdentityAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundRequest whereNote($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundRequest whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundRequest whereUpdatedAt($value)
 * @mixin \Eloquent
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Services\EventLogService\Models\EventLog[] $logs
 * @property-read int|null $logs_count
 */
class FundRequest extends Model
{
    use HasLogs;

    public const EVENT_CREATED = 'created';
    public const EVENT_RESOLVED = 'resolved';
    public const EVENT_APPROVED = 'approved';
    public const EVENT_DECLINED = 'declined';
    public const EVENT_APPROVED_PARTLY = 'approved_partly';
    public const EVENT_CLARIFICATION_REQUESTED = 'clarification_requested';

    public const STATE_PENDING = 'pending';
    public const STATE_APPROVED = 'approved';
    public const STATE_DECLINED = 'declined';
    public const STATE_APPROVED_PARTLY = 'approved_partly';

    public const STATES = [
        self::STATE_PENDING,
        self::STATE_APPROVED,
        self::STATE_DECLINED,
        self::STATE_APPROVED_PARTLY,
    ];

    protected $fillable = [
        'fund_id', 'identity_address', 'employee_id', 'note', 'state',
    ];

    /**
     * @param \Illuminate\Http\Request $request
     * @param Organization|null $organization
     * @param string|null $identity_address
     * @return FundRequest|\Illuminate\Database\Eloquent\Builder
     */
    public static function search(
        Request $request,
        ?Organization $organization = null,
        ?string $identity_address = null
    ) {
        $query = self::query();

        if ($organization && $identity_address) {
            $internalFunds = $organization->funds()->pluck('id');
            $externalFunds = FundQuery::whereExternalValidatorFilter(
                Fund::query(),
                OrganizationQuery::whereHasPermissions(
                    Organization::query(), $identity_address, 'validate_records'
                )->pluck('organizations.id')->toArray(),
                true
            )->pluck('funds.id');

            $query->whereIn('fund_id', $externalFunds->merge($internalFunds)->unique());
        }

        if ($request->has('q') && $q = $request->input('q')) {
            $query->whereHas('fund', static function(Builder $builder) use ($q) {
                $builder->where('name', 'LIKE', "%$q%");
            });
        }

        return $query;
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
    public function clarifications(): HasManyThrough {
        return $this->hasManyThrough(
            FundRequestClarification::class,
            FundRequestRecord::class
        );
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function records_approved(): HasMany {
        return $this->records()->where([
            'fund_request_records.state' => FundRequestRecord::STATE_APPROVED
        ]);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function records_declined(): HasMany {
        return $this->hasMany(FundRequestRecord::class)->where([
            'fund_request_records.state' => FundRequestRecord::STATE_DECLINED
        ]);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function records_pending(): HasMany {
        return $this->hasMany(FundRequestRecord::class)->where([
            'fund_request_records.state' => FundRequestRecord::STATE_PENDING
        ]);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function clarifications_pending(): HasMany {
        return $this->hasMany(FundRequestRecord::class)->where([
            'fund_request_clarifications.state' => FundRequestClarification::STATE_PENDING
        ]);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function clarifications_answered(): HasMany {
        return $this->hasMany(FundRequestRecord::class)->where([
            'fund_request_clarifications.state' => FundRequestClarification::STATE_ANSWERED
        ]);
    }

    /**
     * @param Employee $employee
     * @param string|null $note
     * @return FundRequest|bool
     */
    public function decline(Employee $employee, string $note = null) {
        $this->records_pending()->where([
            'employee_id' => $employee->id
        ])->each(static function(FundRequestRecord $record) use ($note) {
            $record->decline($note, false);
        });

        if ($this->records_pending()->count() === 0) {
            return $this->updateStateByRecords();
        }

        return $this;
    }

    /**
     * @param Employee $employee
     * @param string|null $note
     * @return $this
     */
    public function approve(Employee $employee, string $note = null): self {
        $this->records_pending()->where([
            'employee_id' => $employee->id
        ])->each(static function(FundRequestRecord $record) use ($note) {
            $record->approve($note, false);
        });

        if ($this->records_pending()->count() === 0) {
            return $this->updateStateByRecords();
        }

        return $this;
    }

    /**
     * @return $this
     */
    public function updateStateByRecords(): FundRequest {
        $countAll = $this->records()->count();
        $countApproved = $this->records_approved()->count();
        $allApproved = $countAll === $countApproved;
        $hasApproved = $countApproved > 0;
        $oldState = $this->state;

        $this->update([
            'state' => $allApproved ? self::STATE_APPROVED : (
                $hasApproved ? self::STATE_APPROVED_PARTLY : self::STATE_DECLINED
            )
        ]);

        if (($oldState !== $this->state) && ($this->state !== 'pending')) {
            FundRequestResolved::dispatch($this);
        }

        return $this;
    }

    /**
     * @param Employee $employee
     * @return $this
     */
    public function assignEmployee(Employee $employee): self {
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
     * @param Employee $employee
     * @param FundCriterion|null $fundCriterion
     * @return $this
     */
    public function resignEmployee(
        Employee $employee,
        ?FundCriterion $fundCriterion = null
    ): self {
        $query = $this->records()->where([
            'state' => FundRequestRecord::STATE_PENDING,
            'employee_id' => $employee->id,
        ]);

        if (!is_null($fundCriterion)) {
            $query->where('fund_criterion_id', $fundCriterion->id);
        }

        $query->update([
            'employee_id' => null
        ]);

        return $this;
    }

    /**
     * @param Builder $builder
     * @return Builder[]|Collection|\Illuminate\Support\Collection
     */
    private static function exportTransform(Builder $builder) {
        $transKey = "export.fund_requests";
        $recordRepo = resolve('forus.services.record');

        return $builder->with([
            'records.employee', 'fund'
        ])->get()->map(static function(
            FundRequest $fundRequest
        ) use ($transKey, $recordRepo) {
            return [
                trans("$transKey.bsn") => $recordRepo->bsnByAddress(
                    $fundRequest->identity_address
                ),
                trans("$transKey.fund_name") => $fundRequest->fund->name,
                trans("$transKey.status") => $fundRequest->state,
                trans("$transKey.validator") => $fundRequest->records->filter()->pluck('employee')->count() > 0 ?
                    $fundRequest->records->pluck('employee')->filter()->map(static function(
                        Employee $employee
                    ) use ($recordRepo) {
                        return $recordRepo->primaryEmailByAddress($employee->identity_address);
                    })->unique()->join(', ') : null,
                trans("$transKey.created_at") => $fundRequest->created_at,
            ];
        })->values();
    }

    /**
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
}
