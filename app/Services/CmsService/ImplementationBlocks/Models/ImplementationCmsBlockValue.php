<?php

namespace App\Services\CmsService\ImplementationBlocks\Models;

use App\Services\MediaService\Traits\HasMedia;
use App\Services\TranslationService\Traits\HasOnDemandTranslations;
use App\Traits\HasMarkdownFields;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Services\CmsService\ImplementationBlocks\Models\ImplementationCmsBlockValue.
 *
 * @property int $id
 * @property int $implementation_cms_block_id
 * @property string $field_key
 * @property string|null $value
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Services\CmsService\ImplementationBlocks\Models\ImplementationCmsBlock $implementation_cms_block
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Services\MediaService\Models\Media[] $medias
 * @property-read int|null $medias_count
 * @property-read \Illuminate\Database\Eloquent\Collection $translation_values
 * @property-read int|null $translation_values_count
 * @mixin \Eloquent
 */
class ImplementationCmsBlockValue extends Model
{
    use HasMedia;
    use HasMarkdownFields;
    use HasOnDemandTranslations;

    /**
     * @var string[]
     */
    protected $fillable = [
        'implementation_cms_block_id',
        'field_key',
        'value',
    ];

    /**
     * @return string[]
     */
    public static function getMarkdownKeys(): array
    {
        return [
            'value',
        ];
    }

    /**
     * @return BelongsTo
     */
    public function implementation_cms_block(): BelongsTo
    {
        return $this->belongsTo(ImplementationCmsBlock::class);
    }
}
