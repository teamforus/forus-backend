<?php

namespace App\Models;

use App\Services\FileService\Traits\HasFiles;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Models\ProductReservationFieldValue.
 *
 * @property int $id
 * @property int $reservation_field_id
 * @property int $product_reservation_id
 * @property string|null $value
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Services\FileService\Models\File[] $files
 * @property-read int|null $files_count
 * @property-read \App\Models\ProductReservation $product_reservation
 * @property-read \App\Models\ReservationField $reservation_field
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductReservationFieldValue newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductReservationFieldValue newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductReservationFieldValue query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductReservationFieldValue whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductReservationFieldValue whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductReservationFieldValue whereProductReservationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductReservationFieldValue whereReservationFieldId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductReservationFieldValue whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductReservationFieldValue whereValue($value)
 * @mixin \Eloquent
 */
class ProductReservationFieldValue extends BaseModel
{
    use HasFiles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'reservation_field_id', 'product_reservation_id', 'value',
    ];

    /**
     * @return BelongsTo
     * @noinspection PhpUnused
     */
    public function reservation_field(): BelongsTo
    {
        /** @var BelongsTo|ReservationField $relation */
        $relation = $this->belongsTo(ReservationField::class);

        return $relation->withTrashed();
    }

    /**
     * @return BelongsTo
     * @noinspection PhpUnused
     */
    public function product_reservation(): BelongsTo
    {
        return $this->belongsTo(ProductReservation::class);
    }
}
