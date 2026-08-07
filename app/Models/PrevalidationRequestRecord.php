<?php

namespace App\Models;

use App\Events\PrevalidationRequestRecords\PrevalidationRequestRecordUpdated;
use App\Services\EventLogService\Traits\HasLogs;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Event;

/**
 * @property int $id
 * @property string $record_type_key
 * @property int $prevalidation_request_id
 * @property string $value
 * @property string $source
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\PrevalidationRequest $prevalidation_request
 * @property-read \App\Models\RecordType $record_type
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrevalidationRequestRecord newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrevalidationRequestRecord newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrevalidationRequestRecord query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrevalidationRequestRecord whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrevalidationRequestRecord whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrevalidationRequestRecord wherePrevalidationRequestId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrevalidationRequestRecord whereRecordTypeKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrevalidationRequestRecord whereSource($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrevalidationRequestRecord whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrevalidationRequestRecord whereValue($value)
 * @mixin \Eloquent
 */
class PrevalidationRequestRecord extends Model
{
    use HasLogs;

    public const string EVENT_UPDATED = 'updated';
    public const string SOURCE_FILE = 'file';
    public const string SOURCE_BRP = 'brp';

    /**
     * @var array
     */
    protected $fillable = [
        'record_type_key', 'value', 'source',
    ];

    /**
     * @return BelongsTo
     */
    public function prevalidation_request(): BelongsTo
    {
        return $this->belongsTo(PrevalidationRequest::class);
    }

    /**
     * @return BelongsTo
     */
    public function record_type(): BelongsTo
    {
        return $this->belongsTo(RecordType::class, 'record_type_key', 'key');
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
     * @param Employee|null $employee
     * @return $this
     */
    public function change(string $value, ?Employee $employee): static
    {
        $previousValue = $this->value;
        $this->update(compact('value'));

        Event::dispatch(new PrevalidationRequestRecordUpdated($this, $employee, $previousValue));

        return $this;
    }
}
