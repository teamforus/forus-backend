<?php

namespace App\Models;

use App\Models\Traits\HasFaq;
use App\Services\MediaService\Traits\HasMedia;
use App\Services\TranslationService\Traits\HasOnDemandTranslations;
use App\Traits\HasMarkdownDescription;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Arr;

/**
 * App\Models\ImplementationPage.
 *
 * @property int $id
 * @property int $implementation_id
 * @property string|null $page_type
 * @property string $state
 * @property string|null $title
 * @property string|null $description
 * @property string $description_alignment
 * @property string $description_position
 * @property string|null $external_url
 * @property bool $external
 * @property int $blocks_per_row
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Collection|\App\Models\ImplementationBlock[] $blocks
 * @property-read int|null $blocks_count
 * @property-read Collection|\App\Models\Faq[] $faq
 * @property-read int|null $faq_count
 * @property-read string $description_html
 * @property-read \App\Models\Implementation|null $implementation
 * @property-read Collection|\App\Services\MediaService\Models\Media[] $medias
 * @property-read int|null $medias_count
 * @property-read Collection|\App\Services\TranslationService\Models\TranslationValue[] $translation_values
 * @property-read int|null $translation_values_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImplementationPage newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImplementationPage newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImplementationPage onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImplementationPage query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImplementationPage whereBlocksPerRow($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImplementationPage whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImplementationPage whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImplementationPage whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImplementationPage whereDescriptionAlignment($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImplementationPage whereDescriptionPosition($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImplementationPage whereExternal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImplementationPage whereExternalUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImplementationPage whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImplementationPage whereImplementationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImplementationPage wherePageType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImplementationPage whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImplementationPage whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImplementationPage whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImplementationPage withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImplementationPage withoutTrashed()
 * @mixin \Eloquent
 */
class ImplementationPage extends BaseModel
{
    use HasFaq;
    use HasMedia;
    use SoftDeletes;
    use HasMarkdownDescription;
    use HasOnDemandTranslations;

    public const string TYPE_HOME = 'home';
    public const string TYPE_PRODUCTS = 'products';
    public const string TYPE_PROVIDERS = 'providers';
    public const string TYPE_FUNDS = 'funds';
    public const string TYPE_EXPLANATION = 'explanation';
    public const string TYPE_PROVIDER = 'provider';
    public const string TYPE_PRIVACY = 'privacy';
    public const string TYPE_ACCESSIBILITY = 'accessibility';
    public const string TYPE_TERMS_AND_CONDITIONS = 'terms_and_conditions';
    public const string TYPE_FOOTER_OPENING_TIMES = 'footer_opening_times';
    public const string TYPE_FOOTER_CONTACT_DETAILS = 'footer_contact_details';
    public const string TYPE_FOOTER_APP_INFO = 'footer_app_info';
    public const string TYPE_BLOCK_HOME_PRODUCTS = 'block_home_products';
    public const string TYPE_BLOCK_HOME_PRODUCT_CATEGORIES = 'block_home_product_categories';

    public const string STATE_DRAFT = 'draft';
    public const string STATE_PUBLIC = 'public';

    public const array STATES = [
        self::STATE_DRAFT,
        self::STATE_PUBLIC,
    ];

    public const string DESCRIPTION_POSITION_AFTER = 'after';
    public const string DESCRIPTION_POSITION_BEFORE = 'before';
    public const string DESCRIPTION_POSITION_REPLACE = 'replace';

    public const array DESCRIPTION_POSITIONS = [
        self::DESCRIPTION_POSITION_AFTER,
        self::DESCRIPTION_POSITION_BEFORE,
        self::DESCRIPTION_POSITION_REPLACE,
    ];

    public const array PAGE_TYPES = [[
        'key' => self::TYPE_HOME,
        'type' => 'static',
        'blocks' => true,
        'faq' => false,
        'description_position_configurable' => false,
    ], [
        'key' => self::TYPE_PRODUCTS,
        'type' => 'static',
        'blocks' => false,
        'faq' => false,
        'description_position_configurable' => false,
    ], [
        'key' => self::TYPE_PROVIDERS,
        'type' => 'static',
        'blocks' => false,
        'faq' => false,
        'description_position_configurable' => false,
    ], [
        'key' => self::TYPE_FUNDS,
        'type' => 'static',
        'blocks' => false,
        'faq' => false,
        'description_position_configurable' => false,
    ], [
        'key' => self::TYPE_PROVIDER,
        'type' => 'static',
        'blocks' => true,
        'faq' => false,
        'description_position_configurable' => true,
    ], [
        'key' => self::TYPE_EXPLANATION,
        'type' => 'extra',
        'blocks' => true,
        'faq' => true,
        'description_position_configurable' => true,
    ], [
        'key' => self::TYPE_PRIVACY,
        'type' => 'extra',
        'blocks' => true,
        'faq' => false,
        'description_position_configurable' => true,
    ], [
        'key' => self::TYPE_ACCESSIBILITY,
        'type' => 'extra',
        'blocks' => true,
        'faq' => false,
        'description_position_configurable' => true,
    ], [
        'key' => self::TYPE_TERMS_AND_CONDITIONS,
        'type' => 'extra',
        'blocks' => true,
        'faq' => false,
        'description_position_configurable' => false,
    ], [
        'key' => self::TYPE_FOOTER_CONTACT_DETAILS,
        'type' => 'element',
        'blocks' => false,
        'faq' => false,
        'description_position_configurable' => false,
    ], [
        'key' => self::TYPE_FOOTER_OPENING_TIMES,
        'type' => 'element',
        'blocks' => false,
        'faq' => false,
        'description_position_configurable' => false,
    ], [
        'key' => self::TYPE_FOOTER_APP_INFO,
        'type' => 'element',
        'blocks' => false,
        'faq' => false,
        'description_position_configurable' => true,
    ], [
        'key' => self::TYPE_BLOCK_HOME_PRODUCTS,
        'type' => 'block',
        'blocks' => false,
        'faq' => false,
        'description_position_configurable' => false,
    ], [
        'key' => self::TYPE_BLOCK_HOME_PRODUCT_CATEGORIES,
        'type' => 'block',
        'blocks' => false,
        'faq' => false,
        'description_position_configurable' => false,
    ]];

    /**
     * @var string[]
     */
    protected $fillable = [
        'title', 'implementation_id', 'page_type', 'description', 'description_alignment',
        'external', 'external_url', 'state', 'description_position', 'blocks_per_row',
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
     * @return HasMany
     */
    public function blocks(): HasMany
    {
        return $this->hasMany(ImplementationBlock::class)->orderBy('order');
    }

    /**
     * @param array|null $blocks
     * @return void
     */
    public function syncBlocks(?array $blocks = null): void
    {
        if ($blocks === null) {
            return;
        }

        // remove blocks not listed in the array
        $block_ids = array_filter(Arr::pluck($blocks, 'id'));
        $this->blocks()->whereNotIn('id', $block_ids)->delete();

        foreach ($blocks as $order => $block) {
            $blockData = array_merge(Arr::only($block, [
                'type', 'key', 'media_uid', 'label', 'title', 'description',
                'button_enabled', 'button_text', 'button_link', 'button_target_blank',
                'button_link_label',
            ]), compact('order'));

            /** @var ImplementationBLock $block */
            if ($block = $this->blocks()->find($block['id'] ?? null)) {
                $block->update($blockData);
            } else {
                $block = $this->blocks()->create($blockData);
            }

            $block->attachMediaByUid($blockData['media_uid'] ?? null);
        }
    }

    /**
     * @param string $pageType
     * @return bool
     */
    public static function isInternalType(string $pageType): bool
    {
        $pageType = Arr::keyBy(self::PAGE_TYPES, 'key')[$pageType] ?? null;
        $type = $pageType['type'] ?? null;

        return !$type || in_array($type, ['static', 'page_element']);
    }

    /**
     * @return bool
     */
    public function supportsFaq(): bool
    {
        return Arr::keyBy(self::PAGE_TYPES, 'key')[$this->page_type]['faq'] ?? false;
    }

    /**
     * @return bool
     */
    public function isPublic(): bool
    {
        return $this->state === self::STATE_PUBLIC;
    }

    /**
     * @param string $pageType
     * @return string
     */
    public static function webshopUriByPageType(string $pageType): string
    {
        return match($pageType) {
            'providers' => '/aanbieders',
            'provider' => '/aanbieders/aanmelden',
            'products' => '/aanbod',
            'funds' => '/fondsen',
            'explanation' => '/uitleg',
            'privacy' => '/privacy',
            'accessibility' => '/accessibility',
            'terms_and_conditions' => '/algemene-voorwaarden',
            default => '/',
        };
    }
}
