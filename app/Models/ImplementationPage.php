<?php

namespace App\Models;

use App\Models\Traits\HasFaq;
use App\Services\MediaService\Traits\HasMedia;
use App\Traits\HasMarkdownDescription;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Arr;

/**
 * App\Models\ImplementationPage
 *
 * @property int $id
 * @property int $implementation_id
 * @property string|null $page_type
 * @property string $state
 * @property string|null $description
 * @property string $description_alignment
 * @property string|null $external_url
 * @property bool $external
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Collection|\App\Models\ImplementationBlock[] $blocks
 * @property-read int|null $blocks_count
 * @property-read Collection|\App\Models\Faq[] $faq
 * @property-read int|null $faq_count
 * @property-read string $description_html
 * @property-read string $description_default_position
 * @property-read \App\Models\Implementation $implementation
 * @property-read Collection|\App\Services\MediaService\Models\Media[] $medias
 * @property-read int|null $medias_count
 * @method static \Illuminate\Database\Eloquent\Builder|ImplementationPage newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ImplementationPage newQuery()
 * @method static \Illuminate\Database\Query\Builder|ImplementationPage onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|ImplementationPage query()
 * @method static \Illuminate\Database\Eloquent\Builder|ImplementationPage whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ImplementationPage whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ImplementationPage whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ImplementationPage whereDescriptionAlignment($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ImplementationPage whereExternal($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ImplementationPage whereExternalUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ImplementationPage whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ImplementationPage whereImplementationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ImplementationPage wherePageType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ImplementationPage whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ImplementationPage whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|ImplementationPage withTrashed()
 * @method static \Illuminate\Database\Query\Builder|ImplementationPage withoutTrashed()
 * @mixin \Eloquent
 */
class ImplementationPage extends BaseModel
{
    use HasMedia, HasMarkdownDescription, HasFaq, SoftDeletes;

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

    const STATE_DRAFT = 'draft';
    const STATE_PUBLIC = 'public';

    const STATES = [
        self::STATE_DRAFT,
        self::STATE_PUBLIC,
    ];

    const PAGE_TYPES = [[
        'key' => self::TYPE_HOME,
        'type' => 'static',
        'blocks' => true,
        'faq' => false,
        'default_description_settings' => false,
    ], [
        'key' => self::TYPE_PRODUCTS,
        'type' => 'static',
        'blocks' => false,
        'faq' => false,
        'default_description_settings' => false,
    ], [
        'key' => self::TYPE_PROVIDERS,
        'type' => 'static',
        'blocks' => false,
        'faq' => false,
        'default_description_settings' => false,
    ], [
        'key' => self::TYPE_FUNDS,
        'type' => 'static',
        'blocks' => false,
        'faq' => false,
        'default_description_settings' => false,
    ], [
        'key' => self::TYPE_PROVIDER,
        'type' => 'static',
        'blocks' => true,
        'faq' => false,
        'default_description_settings' => true,
    ], [
        'key' => self::TYPE_EXPLANATION,
        'type' => 'extra',
        'blocks' => true,
        'faq' => true,
        'default_description_settings' => true,
    ], [
        'key' => self::TYPE_PRIVACY,
        'type' => 'extra',
        'blocks' => true,
        'faq' => false,
        'default_description_settings' => true,
    ], [
        'key' => self::TYPE_ACCESSIBILITY,
        'type' => 'extra',
        'blocks' => true,
        'faq' => false,
        'default_description_settings' => true,
    ], [
        'key' => self::TYPE_TERMS_AND_CONDITIONS,
        'type' => 'extra',
        'blocks' => true,
        'faq' => false,
        'default_description_settings' => false,
    ], [
        'key' => self::TYPE_FOOTER_CONTACT_DETAILS,
        'type' => 'element',
        'blocks' => false,
        'faq' => false,
        'default_description_settings' => false,
    ], [
        'key' => self::TYPE_FOOTER_OPENING_TIMES,
        'type' => 'element',
        'blocks' => false,
        'faq' => false,
        'default_description_settings' => false,
    ]];

    /**
     * @var string[]
     */
    protected $fillable = [
        'implementation_id', 'page_type', 'description', 'description_alignment',
        'external', 'external_url', 'state', 'description_default_position',
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
        return $this->hasMany(ImplementationBlock::class);
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

        foreach ($blocks as $block) {
            $blockData = Arr::only($block, [
                'type', 'key', 'media_uid', 'label', 'title', 'description',
                'button_enabled', 'button_text', 'button_link', 'button_target_blank',
            ]);

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
