<?php

namespace App\Services\Forus\Record\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Services\Forus\Record\Models\Record
 *
 * @property int $id
 * @property string $identity_address
 * @property int $record_type_id
 * @property int|null $record_category_id
 * @property string $value
 * @property int $order
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Services\Forus\Record\Models\RecordCategory|null $record_category
 * @property-read \App\Services\Forus\Record\Models\RecordType $record_type
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Services\Forus\Record\Models\RecordValidation[] $validations
 * @property-read int|null $validations_count
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Forus\Record\Models\Record newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Forus\Record\Models\Record newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Forus\Record\Models\Record query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Forus\Record\Models\Record whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Forus\Record\Models\Record whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Forus\Record\Models\Record whereIdentityAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Forus\Record\Models\Record whereOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Forus\Record\Models\Record whereRecordCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Forus\Record\Models\Record whereRecordTypeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Forus\Record\Models\Record whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Forus\Record\Models\Record whereValue($value)
 * @mixin \Eloquent
 */
class Record extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'identity_address', 'record_type_id', 'record_category_id',
        'value', 'order'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function record_type() {
        return $this->belongsTo(RecordType::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function record_category() {
        return $this->belongsTo(RecordCategory::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function validations() {
        return $this->hasMany(RecordValidation::class);
    }
}
