<?php

namespace App\Models;

use App\Services\FileService\Traits\HasFiles;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
 * @property \Illuminate\Support\Carbon|null $answered_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Services\FileService\Models\File[] $files
 * @property-read int|null $files_count
 * @property-read \App\Models\FundRequestRecord $fund_request_record
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundRequestClarification newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundRequestClarification newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundRequestClarification query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundRequestClarification whereAnswer($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundRequestClarification whereAnsweredAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundRequestClarification whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundRequestClarification whereFilesRequirement($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundRequestClarification whereFundRequestRecordId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundRequestClarification whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundRequestClarification whereQuestion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundRequestClarification whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundRequestClarification whereTextRequirement($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundRequestClarification whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class FundRequestClarification extends BaseModel
{
    use HasFiles;

    public const string STATE_PENDING = 'pending';
    public const string STATE_ANSWERED = 'answered';

    public const array STATES = [
        self::STATE_PENDING,
        self::STATE_ANSWERED,
    ];

    protected $fillable = [
        'fund_request_record_id', 'state', 'question', 'answer', 'answered_at',
        'text_requirement', 'files_requirement',
    ];

    protected $casts = [
        'answered_at' => 'datetime',
    ];

    /**
     * @return BelongsTo
     * @noinspection PhpUnused
     */
    public function fund_request_record(): BelongsTo
    {
        return $this->belongsTo(FundRequestRecord::class);
    }
}
