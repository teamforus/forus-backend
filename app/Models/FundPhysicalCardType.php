<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Models\FundPhysicalCardType.
 *
 * @property int $id
 * @property int $fund_id
 * @property int $physical_card_type_id
 * @property bool $allow_physical_card_linking
 * @property bool $allow_physical_card_requests
 * @property bool $allow_physical_card_deactivation
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Fund $fund
 * @property-read \App\Models\PhysicalCardType $physical_card_type
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundPhysicalCardType newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundPhysicalCardType newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundPhysicalCardType query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundPhysicalCardType whereAllowPhysicalCardDeactivation($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundPhysicalCardType whereAllowPhysicalCardLinking($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundPhysicalCardType whereAllowPhysicalCardRequests($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundPhysicalCardType whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundPhysicalCardType whereFundId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundPhysicalCardType whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundPhysicalCardType wherePhysicalCardTypeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundPhysicalCardType whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class FundPhysicalCardType extends Model
{
    protected $fillable = [
        'fund_id',
        'physical_card_type_id',
        'allow_physical_card_linking',
        'allow_physical_card_requests',
        'allow_physical_card_deactivation',
    ];

    protected $casts = [
        'allow_physical_card_linking' => 'boolean',
        'allow_physical_card_requests' => 'boolean',
        'allow_physical_card_deactivation' => 'boolean',
    ];

    /**
     * @return BelongsTo
     * @noinspection PhpUnused
     */
    public function physical_card_type(): BelongsTo
    {
        return $this->belongsTo(PhysicalCardType::class);
    }

    /**
     * @return BelongsTo
     * @noinspection PhpUnused
     */
    public function fund(): BelongsTo
    {
        return $this->belongsTo(Fund::class);
    }
}
