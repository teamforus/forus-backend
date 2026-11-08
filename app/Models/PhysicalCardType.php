<?php

namespace App\Models;

use App\Services\DCPService\Models\DCPOrder;
use App\Services\DCPService\Models\DCPOrderBulk;
use App\Services\MediaService\Models\Media;
use App\Services\MediaService\Traits\HasMedia;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Random\RandomException;
use RuntimeException;

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
 * @property bool $send_to_dcp
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
        'name', 'description', 'organization_id', 'code_blocks', 'code_block_size', 'code_prefix', 'send_to_dcp',
    ];

    /**
     * @var array
     */
    protected $casts = [
        'send_to_dcp' => 'boolean',
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

    /**
     * @throws RandomException
     * @return string
     */
    public function makeUniqueCode(): string
    {
        $prefix = (string) $this->code_prefix;
        $length = $this->code_blocks * $this->code_block_size - strlen($prefix);

        for ($i = 0; $i < 100; $i++) {
            $code = $prefix . implode('', array_map(fn () => random_int(0, 9), range(1, max($length, 1))));

            if (
                !PhysicalCard::where('code', $code)->exists() &&
                !PhysicalCardRequest::where('card_code', $code)->exists()
            ) {
                return $code;
            }
        }

        throw new RuntimeException("Could not generate unique code for physical card type [$this->id].");
    }

    /**
     * @return void
     */
    public function failPendingDCPOrders(): void
    {
        $orders = DCPOrder::query()
            ->where('state', DCPOrder::STATE_PENDING)
            ->whereHasMorph('orderable', PhysicalCardRequest::class, function (Builder $builder) {
                $builder->where('physical_card_type_id', $this->id);
            })
            ->get();

        $orders->each(fn (DCPOrder $order) => $order->fail(trans('dcp.errors.card_type_not_sent')));

        DCPOrderBulk::whereIn('id', $orders->pluck('dcp_order_bulk_id')->filter()->unique())
            ->get()
            ->each(fn (DCPOrderBulk $bulk) => $bulk->updateState());
    }
}
