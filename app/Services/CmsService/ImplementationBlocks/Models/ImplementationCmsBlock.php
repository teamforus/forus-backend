<?php

namespace App\Services\CmsService\ImplementationBlocks\Models;

use App\Models\ImplementationPage;
use App\Services\CmsService\ImplementationBlocks\Configs\CmsBlockConfig;
use App\Services\CmsService\ImplementationBlocks\ImplementationCmsBlockService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * App\Services\CmsService\ImplementationBlocks\Models\ImplementationCmsBlock.
 *
 * @property int $id
 * @property int $implementation_page_id
 * @property string $block_type_key
 * @property int $order
 * @property string $state
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\ImplementationPage $implementation_page
 * @property-read Collection|\App\Services\CmsService\ImplementationBlocks\Models\ImplementationCmsBlockItem[] $items
 * @property-read int|null $items_count
 * @property-read Collection|\App\Services\CmsService\ImplementationBlocks\Models\ImplementationCmsBlockValue[] $values
 * @property-read int|null $values_count
 * @mixin \Eloquent
 */
class ImplementationCmsBlock extends Model
{
    public const string STATE_DRAFT = 'draft';
    public const string STATE_PUBLIC = 'public';

    public const array STATES = [
        self::STATE_DRAFT,
        self::STATE_PUBLIC,
    ];

    /**
     * @var string[]
     */
    protected $fillable = [
        'implementation_page_id',
        'block_type_key',
        'order',
        'state',
    ];

    /**
     * @var string[]
     */
    protected $casts = [
        'order' => 'int',
    ];

    /**
     * @return BelongsTo
     */
    public function implementation_page(): BelongsTo
    {
        return $this->belongsTo(ImplementationPage::class);
    }

    /**
     * @return HasMany
     */
    public function values(): HasMany
    {
        return $this->hasMany(ImplementationCmsBlockValue::class);
    }

    /**
     * @return HasMany
     */
    public function items(): HasMany
    {
        return $this->hasMany(ImplementationCmsBlockItem::class)
            ->orderBy('order')
            ->chaperone('implementation_cms_block');
    }

    /**
     * @return CmsBlockConfig|null
     */
    public function getCmsConfig(): ?CmsBlockConfig
    {
        return ImplementationCmsBlockService::getBlockConfig($this->block_type_key);
    }
}
