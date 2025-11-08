<?php

namespace App\Models;

use App\Services\MediaService\Models\Media;
use App\Services\MediaService\Traits\HasMedia;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Models\PhysicalCardType.
 *
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property int $organization_id
 * @property string|null $code_prefix
 * @property int $code_blocks
 * @property int $code_block_size
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\FundConfig[] $fund_configs
 * @property-read int|null $fund_configs_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\FundPhysicalCardType[] $fund_physical_card_types
 * @property-read int|null $fund_physical_card_types_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Fund[] $funds
 * @property-read int|null $funds_count
 * @property-read \Illuminate\Database\Eloquent\Collection|Media[] $medias
 * @property-read int|null $medias_count
 * @property-read \App\Models\Organization $organization
 * @property-read Media|null $photo
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\PhysicalCard[] $physical_cards
 * @property-read int|null $physical_cards_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PhysicalCardType newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PhysicalCardType newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PhysicalCardType onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PhysicalCardType query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PhysicalCardType whereCodeBlockSize($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PhysicalCardType whereCodeBlocks($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PhysicalCardType whereCodePrefix($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PhysicalCardType whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PhysicalCardType whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PhysicalCardType whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PhysicalCardType whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PhysicalCardType whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PhysicalCardType whereOrganizationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PhysicalCardType whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PhysicalCardType withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PhysicalCardType withoutTrashed()
 * @mixin \Eloquent
 */
class PhysicalCardType extends Model
{
    use HasMedia;
    use SoftDeletes;

    protected $fillable = [
        'name', 'description', 'organization_id', 'code_blocks', 'code_block_size', 'code_prefix',
    ];

    /**
     * Get fund logo.
     * @return MorphOne
     */
    public function photo(): MorphOne
    {
        return $this->morphOne(Media::class, 'mediable')->where([
            'type' => 'physical_card_type_photo',
        ]);
    }

    /**
     * @return BelongsTo
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @return HasMany
     */
    public function physical_cards(): HasMany
    {
        return $this->hasMany(PhysicalCard::class);
    }

    /**
     * @return BelongsToMany
     * @noinspection PhpUnused
     */
    public function funds(): BelongsToMany
    {
        return $this->belongsToMany(
            Fund::class,
            'fund_physical_card_types',
            'physical_card_type_id',
            'fund_id'
        );
    }

    /**
     * @return HasMany
     */
    public function fund_configs(): HasMany
    {
        return $this->hasMany(FundConfig::class, 'fund_request_physical_card_type_id', 'id');
    }

    /**
     * @return HasMany
     * @noinspection PhpUnused
     */
    public function fund_physical_card_types(): HasMany
    {
        return $this->hasMany(FundPhysicalCardType::class);
    }
}
