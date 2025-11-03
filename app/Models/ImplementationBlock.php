<?php

namespace App\Models;

use App\Services\MediaService\Models\Media;
use App\Services\MediaService\Traits\HasMedia;
use App\Services\TranslationService\Traits\HasOnDemandTranslations;
use App\Traits\HasMarkdownFields;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Facades\Config;

/**
 * App\Models\ImplementationBlock.
 *
 * @property int $id
 * @property int $implementation_page_id
 * @property string $type
 * @property string $key
 * @property string|null $label
 * @property string|null $title
 * @property string|null $description
 * @property bool $button_enabled
 * @property string|null $button_text
 * @property string|null $button_link
 * @property string $button_link_label
 * @property bool $button_target_blank
 * @property int $order
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read string $description_html
 * @property-read \App\Models\ImplementationPage $implementation_page
 * @property-read \Illuminate\Database\Eloquent\Collection|Media[] $medias
 * @property-read int|null $medias_count
 * @property-read Media|null $photo
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Services\TranslationService\Models\TranslationValue[] $translation_values
 * @property-read int|null $translation_values_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImplementationBlock newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImplementationBlock newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImplementationBlock query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImplementationBlock whereButtonEnabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImplementationBlock whereButtonLink($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImplementationBlock whereButtonLinkLabel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImplementationBlock whereButtonTargetBlank($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImplementationBlock whereButtonText($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImplementationBlock whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImplementationBlock whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImplementationBlock whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImplementationBlock whereImplementationPageId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImplementationBlock whereKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImplementationBlock whereLabel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImplementationBlock whereOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImplementationBlock whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImplementationBlock whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImplementationBlock whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class ImplementationBlock extends Model
{
    use HasMedia;
    use HasMarkdownFields;
    use HasOnDemandTranslations;

    protected $casts = [
        'button_enabled' => 'bool',
        'button_target_blank' => 'bool',
    ];

    /**
     * @var string[]
     */
    protected $fillable = [
        'implementation_page_id', 'key', 'type', 'label', 'title', 'description',
        'button_enabled', 'button_text', 'button_link', 'button_target_blank',
        'button_link_label', 'order',
    ];

    /**
     * @return BelongsTo
     * @noinspection PhpUnused
     */
    public function implementation_page(): BelongsTo
    {
        return $this->belongsTo(ImplementationPage::class);
    }

    /**
     * Get fund banner.
     * @return MorphOne
     * @noinspection PhpUnused
     */
    public function photo(): MorphOne
    {
        return $this->morphOne(Media::class, 'mediable')->where([
            'type' => 'implementation_block_media',
        ]);
    }

    /**
     * @return array
     */
    protected function getMarkdownConverterConfigs(): array
    {
        $webshopUrl = $this->implementation_page->implementation->urlWebshop();

        return array_merge(Config::get('markdown'), [
            'external_link' => [
                'open_in_new_window' => true,
                'internal_hosts' => parse_url($webshopUrl)['host'] ?? null,
            ],
        ]);
    }
}
