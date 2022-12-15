<?php

namespace App\Models;

use App\Services\MediaService\Models\Media;
use App\Services\MediaService\Traits\HasMedia;
use App\Traits\HasMarkdownDescription;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Facades\Config;

/**
 * App\Models\ImplementationBlock
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
 * @property bool $button_target_blank
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read string $description_html
 * @property-read \App\Models\ImplementationPage $implementation_page
 * @property-read \Illuminate\Database\Eloquent\Collection|Media[] $medias
 * @property-read int|null $medias_count
 * @property-read Media|null $photo
 * @method static \Illuminate\Database\Eloquent\Builder|ImplementationBlock newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ImplementationBlock newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ImplementationBlock query()
 * @method static \Illuminate\Database\Eloquent\Builder|ImplementationBlock whereButtonEnabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ImplementationBlock whereButtonLink($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ImplementationBlock whereButtonTargetBlank($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ImplementationBlock whereButtonText($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ImplementationBlock whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ImplementationBlock whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ImplementationBlock whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ImplementationBlock whereImplementationPageId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ImplementationBlock whereKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ImplementationBlock whereLabel($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ImplementationBlock whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ImplementationBlock whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ImplementationBlock whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class ImplementationBlock extends Model
{
    use HasMedia, HasMarkdownDescription;

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
     * Get fund banner
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
