<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Models\PreCheckRecord
 *
 * @property int $id
 * @property int $record_type_id
 * @property int $pre_check_id
 * @property int|null $order
 * @property string $title
 * @property string|null $description
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\PreCheck $pre_check
 * @property-read \App\Models\RecordType $record_type
 * @method static \Illuminate\Database\Eloquent\Builder|PreCheckRecord newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|PreCheckRecord newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|PreCheckRecord query()
 * @method static \Illuminate\Database\Eloquent\Builder|PreCheckRecord whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PreCheckRecord whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PreCheckRecord whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PreCheckRecord whereOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PreCheckRecord wherePreCheckId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PreCheckRecord whereRecordTypeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PreCheckRecord whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PreCheckRecord whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class PreCheckRecord extends BaseModel
{
    /**
     * @var array
     */
    protected $fillable = [
        'record_type_id', 'pre_check_id', 'order',
        'title', 'short_title', 'description',
    ];

    /**
     * @return BelongsTo
     * @noinspection PhpUnused
     */
    public function pre_check(): BelongsTo
    {
        return $this->belongsTo(PreCheck::class);
    }

    /**
     * @return BelongsTo
     */
    public function record_type(): BelongsTo
    {
        return $this->belongsTo(RecordType::class);
    }
}
