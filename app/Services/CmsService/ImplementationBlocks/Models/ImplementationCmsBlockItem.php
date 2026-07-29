<?php

namespace App\Services\CmsService\ImplementationBlocks\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * App\Services\CmsService\ImplementationBlocks\Models\ImplementationCmsBlockItem.
 *
 * @property int $id
 * @property int $implementation_cms_block_id
 * @property string $item_type_key
 * @property int $order
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Services\CmsService\ImplementationBlocks\Models\ImplementationCmsBlock $implementation_cms_block
 * @property-read Collection|\App\Services\CmsService\ImplementationBlocks\Models\ImplementationCmsBlockItemValue[] $values
 * @property-read int|null $values_count
 * @mixin \Eloquent
 */
class ImplementationCmsBlockItem extends Model
{
    /**
     * @var string[]
     */
    protected $fillable = [
        'implementation_cms_block_id',
        'item_type_key',
        'order',
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
    public function implementation_cms_block(): BelongsTo
    {
        return $this->belongsTo(ImplementationCmsBlock::class);
    }

    /**
     * @return HasMany
     */
    public function values(): HasMany
    {
        return $this->hasMany(ImplementationCmsBlockItemValue::class);
    }

    /**
     * @return array|null
     */
    public function getCmsItemConfig(): ?array
    {
        return $this->implementation_cms_block?->getCmsConfig()?->itemType($this->item_type_key);
    }
}
