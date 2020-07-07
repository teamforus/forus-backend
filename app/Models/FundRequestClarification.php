<?php

namespace App\Models;

use App\Services\FileService\Traits\HasFiles;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Models\FundRequestClarification
 *
 * @property int $id
 * @property int $fund_request_record_id
 * @property string $question
 * @property string $answer
 * @property string $state
 * @property \Illuminate\Support\Carbon|null $answered_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Services\FileService\Models\File[] $files
 * @property-read int|null $files_count
 * @property-read \App\Models\FundRequestRecord $fund_request_record
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundRequestClarification newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundRequestClarification newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundRequestClarification query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundRequestClarification whereAnswer($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundRequestClarification whereAnsweredAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundRequestClarification whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundRequestClarification whereFundRequestRecordId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundRequestClarification whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundRequestClarification whereQuestion($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundRequestClarification whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundRequestClarification whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class FundRequestClarification extends Model
{
    use HasFiles;

    public const STATE_PENDING = 'pending';
    public const STATE_ANSWERED = 'answered';

    public const STATES = [
        self::STATE_PENDING,
        self::STATE_ANSWERED,
    ];

    protected $fillable = [
        'fund_request_record_id', 'state', 'question', 'answer', 'answered_at'
    ];

    protected $dates = [
        'answered_at'
    ];

    /**
     * @return BelongsTo
     */
    public function fund_request_record(): BelongsTo {
        return $this->belongsTo(FundRequestRecord::class);
    }
}
