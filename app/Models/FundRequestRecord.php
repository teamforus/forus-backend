<?php

namespace App\Models;

use App\Events\FundRequestRecords\FundRequestRecordApproved;
use App\Events\FundRequestRecords\FundRequestRecordDeclined;
use App\Services\EventLogService\Traits\HasLogs;
use App\Services\FileService\Traits\HasFiles;
use App\Services\Forus\Record\Models\RecordType;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;


/**
 * App\Models\FundRequestRecord
 *
 * @property int $id
 * @property int $fund_request_id
 * @property int|null $fund_criterion_id
 * @property string $record_type_key
 * @property string $value
 * @property string $note
 * @property string|null $state
 * @property int|null $employee_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Employee|null $employee
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Services\FileService\Models\File[] $files
 * @property-read int|null $files_count
 * @property-read \App\Models\FundCriterion|null $fund_criterion
 * @property-read \App\Models\FundRequest $fund_request
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\FundRequestClarification[] $fund_request_clarifications
 * @property-read int|null $fund_request_clarifications_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Services\EventLogService\Models\EventLog[] $logs
 * @property-read int|null $logs_count
 * @property-read RecordType $record_type
 * @method static \Illuminate\Database\Eloquent\Builder|FundRequestRecord newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|FundRequestRecord newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|FundRequestRecord query()
 * @method static \Illuminate\Database\Eloquent\Builder|FundRequestRecord whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundRequestRecord whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundRequestRecord whereFundCriterionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundRequestRecord whereFundRequestId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundRequestRecord whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundRequestRecord whereNote($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundRequestRecord whereRecordTypeKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundRequestRecord whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundRequestRecord whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundRequestRecord whereValue($value)
 * @mixin \Eloquent
 */
class FundRequestRecord extends Model
{
    use HasFiles, HasLogs;

    public const STATE_PENDING = 'pending';
    public const STATE_APPROVED = 'approved';
    public const STATE_DECLINED = 'declined';
    public const STATE_DISREGARDED = 'disregarded';

    public const EVENT_ASSIGNED = 'assigned';
    public const EVENT_RESIGNED = 'resigned';
    public const EVENT_APPROVED = 'approved';
    public const EVENT_DECLINED = 'declined';
    public const EVENT_CLARIFICATION_REQUESTED = 'clarification_requested';

    public const EVENTS = [
        self::EVENT_ASSIGNED,
        self::EVENT_RESIGNED,
        self::EVENT_APPROVED,
        self::EVENT_DECLINED,
        self::EVENT_CLARIFICATION_REQUESTED,
    ];

    public const STATES = [
        self::STATE_PENDING,
        self::STATE_APPROVED,
        self::STATE_DECLINED,
        self::STATE_DISREGARDED,
    ];

    protected $fillable = [
        'value', 'record_type_key', 'fund_request_id', 'state', 'note',
        'employee_id', 'fund_criterion_id',
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
     * @noinspection PhpUnused
     */
    public function fund_criterion(): BelongsTo
    {
        return $this->belongsTo(FundCriterion::class);
    }

    /**
     * @return BelongsTo
     */
    public function record_type(): BelongsTo
    {
        return $this->belongsTo(RecordType::class, 'record_type_key', 'key');
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
     * Change fund request record state
     * @param string $state
     * @param string|null $note
     * @return FundRequestRecord
     */
    private function setStateAndResolve(string $state, ?string $note = null): FundRequestRecord
    {
        $this->updateModel(compact('state', 'note'));

        if (static::STATE_APPROVED === $state) {
            FundRequestRecordApproved::dispatch($this);
        }

        if (static::STATE_DECLINED === $state) {
            FundRequestRecordDeclined::dispatch($this);
        }

        if ($this->fund_request->records_pending()->doesntExist()) {
            $this->fund_request->resolve();
        }

        return $this;
    }

    /**
     * Approve fund request record
     * @param string|null $note
     * @return $this
     */
    public function approve(?string $note = null): self
    {
        return $this->setStateAndResolve(self::STATE_APPROVED, $note);
    }

    /**
     * Decline fund request record
     * @param string|null $note
     * @return $this
     * @throws \Exception
     */
    public function decline(?string $note = null): self
    {
        return $this->setStateAndResolve(self::STATE_DECLINED, $note);
    }

    /**
     * @param string|null $note
     * @return $this
     */
    public function disregard(?string $note = null): self
    {
        return $this->setStateAndResolve(self::STATE_DISREGARDED, $note);
    }

    /**
     * @return $this
     */
    public function disregardUndo(): self
    {
        return $this->updateModel([
            'state' => self::STATE_PENDING,
        ]);
    }

    /**
     * Make and validate records for requester
     * @return $this
     */
    public function makeValidation(): self
    {
        if ($this->record_type_key === 'partner_bsn' &&
            $hash_bsn_salt = $this->fund_request->fund->fund_config->hash_bsn_salt) {
            $this->applyRecordAndValidation(
                'partner_bsn_hash',
                hash_hmac('sha256', $this->value, $hash_bsn_salt)
            );
        }

        return $this->applyRecordAndValidation($this->record_type_key, $this->value);
    }

    /**
     * @param string $recordTypeKey
     * @param string $value
     * @return FundRequestRecord
     */
    private function applyRecordAndValidation(
        string $recordTypeKey,
        string $value
    ): FundRequestRecord {
        $recordService = resolve('forus.services.record');
        $fundRequest = $this->fund_request;
        $requestIdentityAddress = $fundRequest->identity_address;

        $record = $recordService->recordCreate($requestIdentityAddress, $recordTypeKey, $value);
        $request = $recordService->makeValidationRequest($requestIdentityAddress, $record['id']);

        $recordService->approveValidationRequest(
            $this->employee->identity_address,
            $request['uuid'],
            $fundRequest->fund->organization_id
        );

        return $this;
    }

    /**
     * @return bool
     */
    public function isPending(): bool
    {
        return $this->state === static::STATE_PENDING;
    }
}
