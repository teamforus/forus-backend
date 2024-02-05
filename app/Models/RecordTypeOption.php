<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Models\RecordTypeOption
 *
 * @property int $id
 * @property int $record_type_id
 * @property string $value
 * @property string $name
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\RecordType $record_type
 * @method static \Illuminate\Database\Eloquent\Builder|RecordTypeOption newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RecordTypeOption newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RecordTypeOption query()
 * @method static \Illuminate\Database\Eloquent\Builder|RecordTypeOption whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RecordTypeOption whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RecordTypeOption whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RecordTypeOption whereRecordTypeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RecordTypeOption whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RecordTypeOption whereValue($value)
 * @mixin \Eloquent
 */
class RecordTypeOption extends Model
{
    /**
     * @return BelongsTo
     */
    public function record_type(): BelongsTo
    {
        return $this->belongsTo(RecordType::class);
    }

    protected $fillable = [
        'value', 'name',
    ];
}
