<?php

namespace App\Models;

use App\Services\FileService\Traits\HasFiles;

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
 * @property-read string|null $created_at_locale
 * @property-read string|null $updated_at_locale
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
 */
class FundRequestRecord extends Model
{
    use HasFiles;

    const STATE_PENDING = 'pending';
    const STATE_APPROVED = 'approved';
    const STATE_DECLINED = 'declined';

    const STATES = [
        self::STATE_PENDING,
        self::STATE_APPROVED,
        self::STATE_DECLINED,
    ];

    protected $fillable = [
        'value', 'record_type_key', 'fund_request_id', 'record_type_id',
        'identity_address', 'state', 'note',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function fund_request()
    {
        return $this->belongsTo(FundRequest::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function fund_request_clarifications()
    {
        return $this->hasMany(FundRequestClarification::class);
    }

    /**
     * @param string $state
     * @return FundRequestRecord
     */
    private function setState(string $state)
    {
        return $this->updateModel(compact('state'));
    }

    /**
     * @param bool $resolveRequest
     * @return $this
     * @throws \Exception
     */
    public function approve($resolveRequest = true) {
        $this->setState(self::STATE_APPROVED);

        if ($resolveRequest && (
            $this->fund_request->records_pending()->count() === 0)) {
            $this->fund_request->approve();
        }

        return $this;
    }

    /**
     * @return $this
     */
    public function makeValidation() {
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
            $this->fund_request->employee->identity_address,
            $validationRequest['uuid'],
            $this->fund_request->fund->organization_id
        );

        return $this;
    }

    /**
     * @param string|null $note
     * @param bool $resolveRequest
     * @return $this
     * @throws \Exception
     */
    public function decline(string $note = null, $resolveRequest = true) {
        $this->update([
            'note' => $note,
            'state' => self::STATE_DECLINED,
        ]);

        if ($resolveRequest && (
            $this->fund_request->records_pending()->count() == 0)) {
            $this->fund_request->decline();
        }

        return $this;
    }
}
