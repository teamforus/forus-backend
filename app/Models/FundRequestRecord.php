<?php

namespace App\Models;

use App\Scopes\Builders\FundRequestRecordQuery;
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
 * @property string $state
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
    use HasFiles;

    public const STATE_PENDING = 'pending';
    public const STATE_APPROVED = 'approved';
    public const STATE_DECLINED = 'declined';
    public const STATE_DISREGARDED = 'disregarded';

    public const STATES = [
        self::STATE_PENDING,
        self::STATE_APPROVED,
        self::STATE_DECLINED,
        self::STATE_DISREGARDED,
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
    private function setState(string $state, ?string $note = null): FundRequestRecord
    {
        return $this->updateModel(compact('state', 'note'));
    }

    /**
     * Approve fund request record
     * @param string|null $note
     * @return $this
     */
    public function approve(string $note = null): self {
        $this->setState(self::STATE_APPROVED, $note);

        if (!$this->fund_request->records_pending()->exists()) {
            $this->fund_request->resolve();
        }

        return $this;
    }

    /**
     * Decline fund request record
     * @param string|null $note
     * @return $this
     * @throws \Exception
     */
    public function decline(string $note = null): self {
        $this->setState(self::STATE_DECLINED, $note);

        if (!$this->fund_request->records_pending()->exists()) {
            $this->fund_request->resolve();
        }

        return $this;
    }

    /**
     * @param string|null $note
     * @return $this
     */
    public function disregard(string $note = null): self {
        $this->setState(self::STATE_DISREGARDED, $note);

        return $this;
    }

    /**
     * Make and validate records for requester
     * @return $this
     */
    public function makeValidation(): self {

        if ($this->record_type_key === 'partner_bsn' &&
            $hash_bsn_salt = $this->fund_request->fund->fund_config->hash_bsn_salt) {
            $this->applyRecordAndValidation(
                'partner_bsn_hash',
                hash_hmac('sha256', $this->value, $hash_bsn_salt)
            );
        }

        return $this->applyRecordAndValidation($this->record_type_key, $this->value);
    }

    private function applyRecordAndValidation(
        string $record_type_key,
        string $value
    ): FundRequestRecord {
        $recordService = resolve('forus.services.record');

        $record = $recordService->recordCreate(
            $this->fund_request->identity_address,
            $record_type_key,
            $value
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
     * Identity can see fund request record value
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
     * Identity can assign fund request record to himself for validation
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
     * Identity is assigned as validator for fund request record
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
