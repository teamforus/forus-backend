<?php

namespace App\Models;

use App\Scopes\Builders\FundRequestRecordQuery;
use App\Services\FileService\Traits\HasFiles;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * App\Models\FundRequestRecord
 *
 * @property int $id
 * @property int $fund_request_id
 * @property string $record_type_key
 * @property string $value
 * @property string $note
 * @property string $state
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Services\FileService\Models\File[] $files
 * @property-read int|null $files_count
 * @property-read \App\Models\FundRequest $fund_request
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\FundRequestClarification[] $fund_request_clarifications
 * @property-read int|null $fund_request_clarifications_count
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundRequestRecord newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundRequestRecord newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundRequestRecord query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundRequestRecord whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundRequestRecord whereFundRequestId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundRequestRecord whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundRequestRecord whereNote($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundRequestRecord whereRecordTypeKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundRequestRecord whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundRequestRecord whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundRequestRecord whereValue($value)
 * @mixin \Eloquent
 * @property int|null $employee_id
 * @property-read \App\Models\Employee|null $employee
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundRequestRecord whereEmployeeId($value)
 * @property int|null $fund_criterion_id
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundRequestRecord whereFundCriterionId($value)
 * @property-read \App\Models\FundCriterion|null $fund_criterion
 */
class FundRequestRecord extends Model
{
    use HasFiles;

    public const STATE_PENDING = 'pending';
    public const STATE_APPROVED = 'approved';
    public const STATE_DECLINED = 'declined';

    public const STATES = [
        self::STATE_PENDING,
        self::STATE_APPROVED,
        self::STATE_DECLINED,
    ];

    protected $fillable = [
        'value', 'record_type_key', 'fund_request_id', 'record_type_id',
        'identity_address', 'state', 'note', 'employee_id', 'fund_criterion_id',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function fund_request(): BelongsTo
    {
        return $this->belongsTo(FundRequest::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function fund_criterion(): BelongsTo
    {
        return $this->belongsTo(FundCriterion::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function fund_request_clarifications(): HasMany
    {
        return $this->hasMany(FundRequestClarification::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * @param string $state
     * @param string|null $note
     * @return FundRequestRecord
     */
    private function setState(string $state, ?string $note = null): FundRequestRecord
    {
        return $this->updateModel(compact('state', 'note'));
    }

    /**
     * @param string|null $note
     * @param bool $resolveRequest
     * @return $this
     */
    public function approve(string $note = null, $resolveRequest = true): self {
        $this->setState(self::STATE_APPROVED, $note);
        $this->makeValidation();

        if ($resolveRequest && (
            $this->fund_request->records_pending()->count() === 0)) {
            $this->fund_request->approve($this->employee);
        }

        return $this;
    }

    /**
     * @param string|null $note
     * @param bool $resolveRequest
     * @return $this
     * @throws \Exception
     */
    public function decline(string $note = null, $resolveRequest = true): self {
        $this->setState(self::STATE_DECLINED, $note);

        if ($resolveRequest && (
            $this->fund_request->records_pending()->count() === 0)) {
            $this->fund_request->decline($this->employee);
        }

        return $this;
    }

    /**
     * @return $this
     */
    public function makeValidation(): self {
        $recordService = service_record();

        $record = $recordService->recordCreate(
            $this->fund_request->identity_address,
            $this->record_type_key,
            $this->value
        );

        $validationRequest = $recordService->makeValidationRequest(
            $this->fund_request->identity_address,
            $record['id']
        );

        $recordService->approveValidationRequest(
            $this->employee->identity_address,
            $validationRequest['uuid'],
            $this->fund_request->fund->organization_id
        );

        return $this;
    }

    /**
     * @param $identity_address
     * @param $employee_id
     * @return bool
     */
    public function isValueReadable($identity_address, $employee_id): bool {
        return FundRequestRecordQuery::whereIdentityCanBeValidatorFilter(
            self::whereId($this->id), $identity_address, $employee_id
        )->exists();
    }

    /**
     * @param $identity_address
     * @param $employee_id
     * @return bool
     */
    public function isAssignable($identity_address, $employee_id): bool {
        return FundRequestRecordQuery::whereIdentityCanBeValidatorFilter(
            self::whereId($this->id)->whereDoesntHave('employee'),
            $identity_address,
            $employee_id
        )->exists();
    }

    /**
     * @param $identity_address
     * @param $employee_id
     * @return bool
     */
    public function isAssigned($identity_address, $employee_id): bool {
        return FundRequestRecordQuery::whereIdentityIsAssignedEmployeeFilter(
            self::whereId($this->id), $identity_address, $employee_id
        )->exists();
    }
}
