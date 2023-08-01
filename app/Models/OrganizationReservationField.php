<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Models\OrganizationReservationField
 *
 * @property int $id
 * @property int $organization_id
 * @property string $label
 * @property string $type
 * @property string|null $description
 * @property bool $required
 * @property int $order
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Organization $organization
 * @method static \Illuminate\Database\Eloquent\Builder|OrganizationReservationField newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|OrganizationReservationField newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|OrganizationReservationField query()
 * @method static \Illuminate\Database\Eloquent\Builder|OrganizationReservationField whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrganizationReservationField whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrganizationReservationField whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrganizationReservationField whereLabel($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrganizationReservationField whereOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrganizationReservationField whereOrganizationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrganizationReservationField whereRequired($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrganizationReservationField whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrganizationReservationField whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class OrganizationReservationField extends BaseModel
{
    use SoftDeletes;

    public const TYPE_TEXT = 'text';
    public const TYPE_NUMBER = 'number';

    public const TYPES = [
        self::TYPE_TEXT,
        self::TYPE_NUMBER,
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'organization_id', 'label', 'type', 'description', 'required', 'order',
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
}
