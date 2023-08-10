<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Models\ProductReservationFieldValue
 *
 * @property int $id
 * @property int $organization_reservation_field_id
 * @property int $product_reservation_id
 * @property string|null $value
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\OrganizationReservationField $organization_reservation_field
 * @property-read \App\Models\ProductReservation $product_reservation
 * @method static \Illuminate\Database\Eloquent\Builder|ProductReservationFieldValue newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProductReservationFieldValue newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProductReservationFieldValue query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProductReservationFieldValue whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductReservationFieldValue whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductReservationFieldValue whereOrganizationReservationFieldId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductReservationFieldValue whereProductReservationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductReservationFieldValue whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductReservationFieldValue whereValue($value)
 * @mixin \Eloquent
 */
class ProductReservationFieldValue extends BaseModel
{

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'organization_reservation_field_id', 'product_reservation_id', 'value',
    ];

    /**
     * @return BelongsTo
     * @noinspection PhpUnused
     */
    public function organization_reservation_field(): BelongsTo
    {
        /** @var BelongsTo|OrganizationReservationField $relation */
        $relation = $this->belongsTo(OrganizationReservationField::class);

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
