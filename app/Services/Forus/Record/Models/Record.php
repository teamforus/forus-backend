<?php

namespace App\Services\Forus\Record\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Services\Forus\Record\Models\Record
 *
 * @property int $id
 * @property string $identity_address
 * @property int $record_type_id
 * @property int|null $record_category_id
 * @property int|null $prevalidation_id
 * @property string $value
 * @property int $order
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Services\Forus\Record\Models\RecordCategory|null $record_category
 * @property-read \App\Services\Forus\Record\Models\RecordType $record_type
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Services\Forus\Record\Models\RecordValidation[] $validations
 * @property-read int|null $validations_count
 * @method static \Illuminate\Database\Eloquent\Builder|Record newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Record newQuery()
 * @method static \Illuminate\Database\Query\Builder|Record onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|Record query()
 * @method static \Illuminate\Database\Eloquent\Builder|Record whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Record whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Record whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Record whereIdentityAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Record whereOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Record wherePrevalidationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Record whereRecordCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Record whereRecordTypeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Record whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Record whereValue($value)
 * @method static \Illuminate\Database\Query\Builder|Record withTrashed()
 * @method static \Illuminate\Database\Query\Builder|Record withoutTrashed()
 * @mixin \Eloquent
 */
class Record extends Model
{
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'identity_address', 'record_type_id', 'record_category_id',
        'value', 'order', 'prevalidation_id',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     * @noinspection PhpUnused
     */
    public function record_type(): BelongsTo
    {
        return $this->belongsTo(RecordType::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     * @noinspection PhpUnused
     */
    public function record_category(): BelongsTo
    {
        return $this->belongsTo(RecordCategory::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     * @noinspection PhpUnused
     */
    public function validations(): HasMany
    {
        return $this->hasMany(RecordValidation::class);
    }
}
