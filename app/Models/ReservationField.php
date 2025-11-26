<?php

namespace App\Models;

use App\Services\TranslationService\Traits\HasOnDemandTranslations;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Models\ReservationField.
 *
 * @property int $id
 * @property int $organization_id
 * @property int|null $product_id
 * @property string $label
 * @property string $type
 * @property string|null $description
 * @property bool $required
 * @property int $order
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\Organization $organization
 * @property-read \App\Models\Product|null $product
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Services\TranslationService\Models\TranslationValue[] $translation_values
 * @property-read int|null $translation_values_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReservationField newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReservationField newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReservationField onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReservationField query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReservationField whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReservationField whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReservationField whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReservationField whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReservationField whereLabel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReservationField whereOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReservationField whereOrganizationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReservationField whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReservationField whereRequired($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReservationField whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReservationField whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReservationField withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReservationField withoutTrashed()
 * @mixin \Eloquent
 */
class ReservationField extends BaseModel
{
    use SoftDeletes;
    use HasOnDemandTranslations;

    public const string TYPE_TEXT = 'text';
    public const string TYPE_FILE = 'file';
    public const string TYPE_NUMBER = 'number';
    public const string TYPE_BOOLEAN = 'boolean';

    public const array TYPES = [
        self::TYPE_TEXT,
        self::TYPE_FILE,
        self::TYPE_NUMBER,
        self::TYPE_BOOLEAN,
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'organization_id', 'label', 'type', 'description', 'required', 'order', 'product_id',
    ];

    /**
     * @var array
     */
    protected $casts = [
        'required' => 'boolean',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     * @noinspection PhpUnused
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @return BelongsTo
     * @noinspection PhpUnused
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
