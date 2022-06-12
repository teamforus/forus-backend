<?php

namespace App\Models;

use App\Services\MediaService\Traits\HasMedia;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * App\Models\ImplementationPage
 *
 * @property int $id
 * @property int $implementation_id
 * @property string|null $page_type
 * @property string|null $content
 * @property string $content_alignment
 * @property string|null $external_url
 * @property bool $external
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read string $content_html
 * @property-read \App\Models\Implementation $implementation
 * @property-read Collection|\App\Models\ImplementationBlock[] $blocks
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Services\MediaService\Models\Media[] $medias
 * @property-read int|null $medias_count
 * @method static \Illuminate\Database\Eloquent\Builder|ImplementationPage newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ImplementationPage newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ImplementationPage query()
 * @method static \Illuminate\Database\Eloquent\Builder|ImplementationPage whereContent($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ImplementationPage whereContentAlignment($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ImplementationPage whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ImplementationPage whereExternal($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ImplementationPage whereExternalUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ImplementationPage whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ImplementationPage whereImplementationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ImplementationPage wherePageType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ImplementationPage whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class ImplementationPage extends Model
{
    use HasMedia;

    const TYPE_HOME = 'home';
    const TYPE_PRODUCTS = 'products';
    const TYPE_PROVIDERS = 'providers';
    const TYPE_FUNDS = 'funds';
    const TYPE_EXPLANATION = 'explanation';
    const TYPE_PROVIDER = 'provider';
    const TYPE_PRIVACY = 'privacy';
    const TYPE_ACCESSIBILITY = 'accessibility';
    const TYPE_TERMS_AND_CONDITIONS = 'terms_and_conditions';
    const TYPE_FOOTER_OPENING_TIMES = 'footer_opening_times';
    const TYPE_FOOTER_CONTACT_DETAILS = 'footer_contact_details';

    const TYPES = [
        self::TYPE_PROVIDER,
        self::TYPE_EXPLANATION,
        self::TYPE_PRIVACY,
        self::TYPE_ACCESSIBILITY,
        self::TYPE_TERMS_AND_CONDITIONS,
        self::TYPE_FOOTER_CONTACT_DETAILS,
        self::TYPE_FOOTER_OPENING_TIMES,
        self::TYPE_HOME,
        self::TYPE_PRODUCTS,
        self::TYPE_PROVIDERS,
        self::TYPE_FUNDS,
    ];

    const TYPES_INTERNAL = [
        self::TYPE_PROVIDER,
        self::TYPE_FOOTER_OPENING_TIMES,
        self::TYPE_FOOTER_CONTACT_DETAILS,
    ];

    const PAGE_BLOCK_LIST = [
        self::TYPE_HOME => [
            ImplementationBlock::TYPE_DETAILED  => ['funds_block'],
            ImplementationBlock::TYPE_TEXT => ['below_header'],
        ],
        self::TYPE_PRODUCTS => [
            ImplementationBlock::TYPE_DETAILED => [],
            ImplementationBlock::TYPE_TEXT => ['above_product_list'],
        ],
        self::TYPE_PROVIDERS => [
            ImplementationBlock::TYPE_DETAILED => [],
            ImplementationBlock::TYPE_TEXT => ['above_provider_list'],
        ],
        self::TYPE_FUNDS => [
            ImplementationBlock::TYPE_DETAILED => [],
            ImplementationBlock::TYPE_TEXT => ['above_fund_list'],
        ],
    ];

    /**
     * @var string[]
     */
    protected $fillable = [
        'implementation_id', 'page_type', 'content', 'content_alignment',
        'external', 'external_url',
    ];

    /**
     * @var string[]
     */
    protected $casts = [
        'external' => 'bool',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function implementation(): BelongsTo
    {
        return $this->belongsTo(Implementation::class);
    }

    /**
     * @return string
     * @noinspection PhpUnused
     */
    public function getContentHtmlAttribute(): string
    {
        return resolve('markdown.converter')->convert($this->content ?: '')->getContent();
    }

    /**
     * @return HasMany
     */
    public function blocks(): HasMany
    {
        return $this->hasMany(ImplementationBlock::class);
    }

    /**
     * @param $page_key
     * @return \string[][]
     */
    public static function getBlockListByPageKey($page_key): array
    {
        $no_blocks = [
            ImplementationBlock::TYPE_TEXT => [],
            ImplementationBlock::TYPE_DETAILED => []
        ];

        return array_key_exists($page_key, self::PAGE_BLOCK_LIST) ? self::PAGE_BLOCK_LIST[$page_key] : $no_blocks;
    }

    /**
     * @param $pageData
     * @return void
     */
    private function syncBlocks($pageData): void
    {
        // remove blocks not listed in the array
        $block_ids = array_filter(array_pluck($pageData['blocks'], 'id'));
        $this->blocks()->whereNotIn('id', $block_ids)->delete();

        if (isset($pageData['blocks'])) {
            foreach ($pageData['blocks'] as $block) {
                $blockData = array_only($block, [
                    'type', 'key', 'media_uid', 'label', 'title', 'description',
                    'button_enabled', 'button_text', 'button_link'
                ]);

                /** @var ImplementationBLock $block */
                if ($block = $this->blocks()->find($block['id'] ?? null)) {
                    $block = tap($block)->update($blockData);
                } else {
                    $block = $this->blocks()->create($blockData);
                }

                $block->appendMedia($blockData['media_uid'] ?? [], 'implementation_block_media');
            }
        }
    }

    /**
     * @param array $data
     * @return $this
     */
    public function change(array $data) : self {
        $this->updateModel(array_merge(array_only($data, [
            'content', 'content_alignment', 'external', 'external_url',
        ]), in_array($data['page_type'], ImplementationPage::TYPES_INTERNAL) ? [
            'external' => 0,
            'external_url' => null,
        ] : []))->appendMedia($data['media_uid'] ?? [], 'implementation_block_media');

        $this->syncBlocks($data);

        return $this;
    }
}
