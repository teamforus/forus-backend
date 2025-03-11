<?php

namespace App\Models;

use App\Events\FundRequestRecords\FundRequestRecordUpdated;
use App\Services\EventLogService\Traits\HasLogs;
use App\Services\FileService\Traits\HasFiles;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * App\Models\FundRequestRecord.
 *
 * @property int $id
 * @property int $fund_request_id
 * @property int|null $fund_criterion_id
 * @property string $record_type_key
 * @property string $value
 * @property string $note
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Collection|\App\Services\FileService\Models\File[] $files
 * @property-read int|null $files_count
 * @property-read \App\Models\FundCriterion|null $fund_criterion
 * @property-read \App\Models\FundRequest $fund_request
 * @property-read Collection|\App\Models\FundRequestClarification[] $fund_request_clarifications
 * @property-read int|null $fund_request_clarifications_count
 * @property-read Collection|\App\Services\EventLogService\Models\EventLog[] $logs
 * @property-read int|null $logs_count
 * @property-read \App\Models\RecordType|null $record_type
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundRequestRecord newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundRequestRecord newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundRequestRecord query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundRequestRecord whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundRequestRecord whereFundCriterionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundRequestRecord whereFundRequestId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundRequestRecord whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundRequestRecord whereNote($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundRequestRecord whereRecordTypeKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundRequestRecord whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundRequestRecord whereValue($value)
 * @mixin \Eloquent
 */
class FundRequestRecord extends BaseModel
{
    use HasFiles;
    use HasLogs;

    public const string EVENT_CLARIFICATION_REQUESTED = 'clarification_requested';
    public const string EVENT_CLARIFICATION_RECEIVED = 'clarification_received';
    public const string EVENT_UPDATED = 'updated';

    protected $fillable = [
        'value', 'record_type_key', 'fund_request_id', 'fund_criterion_id',
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
     * @noinspection PhpUnused
     */
    public function fund_request_clarifications(): HasMany
    {
        return $this->hasMany(FundRequestClarification::class);
    }

    /**
     * Make and validate records for requester.
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
     * @return Collection
     */
    public function historyLogs(): Collection
    {
        return $this->logs->sortByDesc('created_at')->where('event', self::EVENT_UPDATED);
    }

    /**
     * @param string $value
     * @param Employee $employee
     * @return self
     */
    public function updateAsValidator(string $value, Employee $employee): self
    {
        $previousValue = $this->value;
        $this->update(compact('value'));

        FundRequestRecordUpdated::dispatch($this, $employee, null, $previousValue);

        return $this;
    }

    /**
     * @param string $recordTypeKey
     * @param string $value
     * @return FundRequestRecord
     */
    private function applyRecordAndValidation(string $recordTypeKey, string $value): FundRequestRecord
    {
        $this->fund_request->identity
            ->makeRecord(RecordType::findByKey($recordTypeKey), $value)
            ->makeValidationRequest()
            ->approve($this->fund_request->employee->identity, $this->fund_request->fund->organization);

        return $this;
    }
}
