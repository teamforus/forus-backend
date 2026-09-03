<?php

namespace App\Models;

use App\Events\FundRequestClarifications\FundRequestClarificationClosed;
use App\Helpers\Arr;
use App\Services\EventLogService\Models\EventLog;
use App\Services\FileService\Traits\HasFiles;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;

/**
 * App\Models\FundRequestClarification.
 *
 * @property int $id
 * @property int $fund_request_record_id
 * @property string $question
 * @property string $text_requirement
 * @property string $files_requirement
 * @property string $answer
 * @property string $state
 * @property string|null $resolved_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Services\FileService\Models\File[] $files
 * @property-read int|null $files_count
 * @property-read \App\Models\FundRequestRecord $fund_request_record
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundRequestClarification newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundRequestClarification newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundRequestClarification query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundRequestClarification whereAnswer($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundRequestClarification whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundRequestClarification whereFilesRequirement($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundRequestClarification whereFundRequestRecordId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundRequestClarification whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundRequestClarification whereQuestion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundRequestClarification whereResolvedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundRequestClarification whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundRequestClarification whereTextRequirement($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundRequestClarification whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class FundRequestClarification extends Model
{
    use HasFiles;

    public const string STATE_PENDING = 'pending';
    public const string STATE_ANSWERED = 'answered';
    public const string STATE_CLOSED = 'closed';

    public const array STATES = [
        self::STATE_PENDING,
        self::STATE_ANSWERED,
    ];

    protected $fillable = [
        'fund_request_record_id', 'state', 'question', 'answer', 'resolved_at',
        'text_requirement', 'files_requirement',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
    ];

    /**
     * @return BelongsTo
     * @noinspection PhpUnused
     */
    public function fund_request_record(): BelongsTo
    {
        return $this->belongsTo(FundRequestRecord::class);
    }

    /**
     * @return Carbon|null
     */
    public function getLastChangedAt(): ?Carbon
    {
        return $this->fund_request_record
            ->logs
            ->filter(function (EventLog $eventLog) {
                return
                    $eventLog->event === FundRequestRecord::EVENT_CLARIFICATION_UPDATED &&
                    Arr::get($eventLog->data, 'fund_request_clarification_id') === $this->id;
            })
            ->sortByDesc('created_at')
            ->first()
            ?->created_at;
    }

    /**
     * @param string|null $note
     * @param bool $notifyRequester
     * @param Employee|null $employee
     * @return void
     */
    public function close(?string $note, bool $notifyRequester, ?Employee $employee): void
    {
        $this->update([
            'state' => $this::STATE_CLOSED,
            'resolved_at' => now(),
        ]);

        $noteText = trans('fund_request.clarification_closed', [
            'record' => $this->fund_request_record->record_type->name,
        ]);

        if ($note) {
            $noteText .= "\n\n" . $note;
        }

        $this
            ->fund_request_record
            ->fund_request
            ->addNote($noteText, $employee);

        Event::dispatch(new FundRequestClarificationClosed($this, $notifyRequester));
    }
}
