<?php

namespace App\Models;

use App\Http\Resources\MediaResource;
use App\Services\DigIdService\Repositories\DigIdRepo;
use App\Services\Forus\Notification\EmailFrom;
use App\Services\MediaService\MediaImageConfig;
use App\Services\MediaService\MediaImagePreset;
use App\Services\MediaService\MediaPreset;
use App\Services\MediaService\MediaService;
use App\Services\MediaService\Models\Media;
use App\Services\MediaService\Traits\HasMedia;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

/**
 * App\Models\Implementation
 *
 * @property int $id
 * @property int|null $organization_id
 * @property string $key
 * @property string $name
 * @property string|null $title
 * @property string|null $description
 * @property string $description_alignment
 * @property bool $overlay_enabled
 * @property string $overlay_type
 * @property string $header_text_color
 * @property int $overlay_opacity
 * @property string $url_webshop
 * @property string $url_sponsor
 * @property string $url_provider
 * @property string $url_validator
 * @property string $url_app
 * @property float|null $lon
 * @property float|null $lat
 * @property bool $informal_communication
 * @property string|null $email_from_address
 * @property string|null $email_from_name
 * @property bool $digid_enabled
 * @property bool $digid_required
 * @property string $digid_env
 * @property string|null $digid_app_id
 * @property string|null $digid_shared_secret
 * @property string|null $digid_a_select_server
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Media|null $banner
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\FundConfig[] $fund_configs
 * @property-read int|null $fund_configs_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Fund[] $funds
 * @property-read int|null $funds_count
 * @property-read string $description_html
 * @property-read \Illuminate\Database\Eloquent\Collection|Media[] $medias
 * @property-read int|null $medias_count
 * @property-read \App\Models\Organization|null $organization
 * @property-read \App\Models\ImplementationPage|null $page_accessibility
 * @property-read \App\Models\ImplementationPage|null $page_explanation
 * @property-read \App\Models\ImplementationPage|null $page_privacy
 * @property-read \App\Models\ImplementationPage|null $page_provider
 * @property-read \App\Models\ImplementationPage|null $page_terms_and_conditions
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\ImplementationPage[] $pages
 * @property-read int|null $pages_count
 * @method static Builder|Implementation newModelQuery()
 * @method static Builder|Implementation newQuery()
 * @method static Builder|Implementation query()
 * @method static Builder|Implementation whereCreatedAt($value)
 * @method static Builder|Implementation whereDescription($value)
 * @method static Builder|Implementation whereDescriptionAlignment($value)
 * @method static Builder|Implementation whereDigidASelectServer($value)
 * @method static Builder|Implementation whereDigidAppId($value)
 * @method static Builder|Implementation whereDigidEnabled($value)
 * @method static Builder|Implementation whereDigidEnv($value)
 * @method static Builder|Implementation whereDigidRequired($value)
 * @method static Builder|Implementation whereDigidSharedSecret($value)
 * @method static Builder|Implementation whereEmailFromAddress($value)
 * @method static Builder|Implementation whereEmailFromName($value)
 * @method static Builder|Implementation whereHeaderTextColor($value)
 * @method static Builder|Implementation whereId($value)
 * @method static Builder|Implementation whereInformalCommunication($value)
 * @method static Builder|Implementation whereKey($value)
 * @method static Builder|Implementation whereLat($value)
 * @method static Builder|Implementation whereLon($value)
 * @method static Builder|Implementation whereName($value)
 * @method static Builder|Implementation whereOrganizationId($value)
 * @method static Builder|Implementation whereOverlayEnabled($value)
 * @method static Builder|Implementation whereOverlayOpacity($value)
 * @method static Builder|Implementation whereOverlayType($value)
 * @method static Builder|Implementation whereTitle($value)
 * @method static Builder|Implementation whereUpdatedAt($value)
 * @method static Builder|Implementation whereUrlApp($value)
 * @method static Builder|Implementation whereUrlProvider($value)
 * @method static Builder|Implementation whereUrlSponsor($value)
 * @method static Builder|Implementation whereUrlValidator($value)
 * @method static Builder|Implementation whereUrlWebshop($value)
 * @mixin \Eloquent
 */
class Implementation extends Model
{
    use HasMedia;

    public const KEY_GENERAL = 'general';

    public const FRONTEND_WEBSHOP = 'webshop';
    public const FRONTEND_SPONSOR_DASHBOARD = 'sponsor';
    public const FRONTEND_PROVIDER_DASHBOARD = 'provider';
    public const FRONTEND_VALIDATOR_DASHBOARD = 'validator';

    public const FRONTEND_KEYS = [
        self::FRONTEND_WEBSHOP,
        self::FRONTEND_SPONSOR_DASHBOARD,
        self::FRONTEND_PROVIDER_DASHBOARD,
        self::FRONTEND_VALIDATOR_DASHBOARD,
    ];

    protected $perPage = 20;

    /**
     * @var string[]
     */
    protected $fillable = [
        'id', 'key', 'name', 'url_webshop', 'url_sponsor', 'url_provider',
        'url_validator', 'lon', 'lat', 'email_from_address', 'email_from_name',
        'title', 'description', 'description_alignment', 'informal_communication',
        'digid_app_id', 'digid_shared_secret', 'digid_a_select_server', 'digid_enabled',
        'overlay_enabled', 'overlay_type', 'overlay_opacity', 'header_text_color',
    ];

    /**
     * @var string[]
     */
    protected $hidden = [
        'digid_enabled', 'digid_env', 'digid_app_id', 'digid_shared_secret',
        'digid_a_select_server'
    ];

    /**
     * @var string[]
     */
    protected $casts = [
        'lon' => 'float',
        'lat' => 'float',
        'digid_enabled' => 'boolean',
        'digid_required' => 'boolean',
        'overlay_opacity' => 'int',
        'overlay_enabled' => 'boolean',
        'informal_communication' => 'boolean',
    ];

    /**
     * @return HasMany
     */
    public function pages(): HasMany
    {
        return $this->hasMany(ImplementationPage::class);
    }

    /**
     * @return HasOne
     * @noinspection PhpUnused
     */
    public function page_explanation(): HasOne
    {
        return $this->hasOne(ImplementationPage::class)->where([
            'page_type' => ImplementationPage::TYPE_EXPLANATION,
        ]);
    }

    /**
     * @return HasOne
     * @noinspection PhpUnused
     */
    public function page_provider(): HasOne
    {
        return $this->hasOne(ImplementationPage::class)->where([
            'page_type' => ImplementationPage::TYPE_PROVIDER,
        ]);
    }

    /**
     * Get fund banner
     * @return MorphOne
     */
    public function banner(): MorphOne
    {
        return $this->morphOne(Media::class, 'mediable')->where([
            'type' => 'implementation_banner'
        ]);
    }

    /**
     * @return HasOne
     * @noinspection PhpUnused
     */
    public function page_privacy(): HasOne
    {
        return $this->hasOne(ImplementationPage::class)->where([
            'page_type' => ImplementationPage::TYPE_PRIVACY,
        ]);
    }

    /**
     * @return HasOne
     * @noinspection PhpUnused
     */
    public function page_accessibility(): HasOne
    {
        return $this->hasOne(ImplementationPage::class)->where([
            'page_type' => ImplementationPage::TYPE_ACCESSIBILITY,
        ]);
    }

    /**
     * @return HasOne
     * @noinspection PhpUnused
     */
    public function page_terms_and_conditions(): HasOne
    {
        return $this->hasOne(ImplementationPage::class)->where([
            'page_type' => ImplementationPage::TYPE_TERMS_AND_CONDITIONS,
        ]);
    }

    /**
     * @return HasManyThrough
     */
    public function funds(): HasManyThrough
    {
        return $this->hasManyThrough(
            Fund::class,
            FundConfig::class,
            'implementation_id',
            'id',
            'id',
            'fund_id'
        );
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @return array|string|null
     */
    public static function activeKey()
    {
        return request()->header('Client-Key', self::KEY_GENERAL);
    }

    /**
     * @return Implementation
     */
    public static function active(): Implementation
    {
        return self::byKey(self::activeKey());
    }

    /**
     * @param $key
     * @return Implementation|null|Model
     */
    public static function byKey($key): ?Implementation
    {
        return self::where(compact('key'))->first();
    }

    /**
     * @param $key
     * @return bool
     */
    public static function isValidKey($key): bool
    {
        return self::implementationKeysAvailable()->search($key) !== false;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function fund_configs(): HasMany
    {
        return $this->hasMany(FundConfig::class);
    }

    /**
     * @return Builder
     */
    public static function activeFundsQuery(): Builder
    {
        return self::queryFundsByState('active');
    }

    /**
     * @param $states
     * @return Builder
     */
    public static function queryFundsByState($states): Builder
    {
        /** @var Builder $query */
        $query = Fund::query()->has('fund_config')->whereIn('state', (array)$states);

        if (self::activeKey() !== self::KEY_GENERAL) {
            $query->whereHas('fund_config.implementation', static function (Builder $builder) {
                $builder->where('key', self::activeKey());
            });
        }

        return $query;
    }

    /**
     * @return Collection
     */
    public static function activeFunds(): Collection
    {
        return self::activeFundsQuery()->get();
    }

    /**
     * @return Collection
     */
    public static function implementationKeysAvailable(): Collection
    {
        return self::query()->pluck('key');
    }

    /**
     * @return Collection
     */
    public static function keysAvailable(): Collection
    {
        return self::implementationKeysAvailable()->map(static function ($key) {
            return [
                $key . '_webshop',
                $key . '_sponsor',
                $key . '_provider',
                $key . '_validator',
                $key . '_website',
            ];
        })->flatten()->merge(config('forus.clients.mobile'))->values();
    }

    /**
     * @return bool
     */
    public function digidEnabled(): bool
    {
        $digidConfigured =
            !empty($this->digid_app_id) &&
            !empty($this->digid_shared_secret) &&
            !empty($this->digid_a_select_server);

        return $this->digid_enabled && $digidConfigured;
    }

    /**
     * @return DigIdRepo
     * @throws \App\Services\DigIdService\DigIdException
     */
    public function getDigid(): DigIdRepo
    {
        return new DigIdRepo(
            $this->digid_env,
            $this->digid_app_id,
            $this->digid_shared_secret,
            $this->digid_a_select_server
        );
    }

    /**
     * @param string $frontend
     * @param string $uri
     * @return string|null
     */
    public function urlFrontend(string $frontend, string $uri = ''): ?string
    {
        switch ($frontend) {
            case 'webshop':
                return $this->urlWebshop($uri);
            case 'sponsor':
                return $this->urlSponsorDashboard($uri);
            case 'provider':
                return $this->urlProviderDashboard($uri);
            case 'validator':
                return $this->urlValidatorDashboard($uri);
        }

        return null;
    }

    /**
     * @param string $uri
     * @return string
     */
    public function urlWebshop(string $uri = "/"): string
    {
        return http_resolve_url($this->url_webshop, $uri);
    }

    /**
     * @param string $uri
     * @return string
     */
    public function urlSponsorDashboard(string $uri = "/"): string
    {
        return http_resolve_url($this->url_sponsor, $uri);
    }

    /**
     * @param string $uri
     * @return string
     */
    public function urlProviderDashboard(string $uri = "/"): string
    {
        return http_resolve_url($this->url_provider, $uri);
    }

    /**
     * @param string $uri
     * @return string
     */
    public function urlValidatorDashboard(string $uri = "/"): string
    {
        return http_resolve_url($this->url_validator, $uri);
    }

    /**
     * @return bool
     */
    public function autoValidationEnabled(): bool
    {
        $oneActiveFund = $this->funds()->where(['state' => Fund::STATE_ACTIVE])->count() === 1;
        $oneActiveFundWithAutoValidation = $this->funds()->where([
                'state' => Fund::STATE_ACTIVE,
                'auto_requests_validation' => true
            ])->whereNotNull('default_validator_employee_id')->count() === 1;

        return $oneActiveFund && $oneActiveFundWithAutoValidation;
    }

    /**
     * @param $value
     * @return array|\Illuminate\Config\Repository|\Illuminate\Contracts\Foundation\Application|mixed|void
     */
    public static function platformConfig($value)
    {
        if (!self::isValidKey(self::activeKey())) {
            abort(403, 'unknown_implementation_key');
        }

        $ver = request()->input('ver');

        if (preg_match('/[^a-z_\-0-9]/i', $value) || preg_match('/[^a-z_\-0-9]/i', $ver)) {
            abort(403);
        }

        $config = config('forus.features.' . $value . ($ver ? '.' . $ver : ''));

        if (is_array($config)) {
            $implementation = self::active();
            $banner = $implementation->banner;

            $config = array_merge($config, [
                'media' => self::getPlatformMediaConfig(),
                'has_budget_funds' => self::hasFundsOfType(Fund::TYPE_BUDGET),
                'has_subsidy_funds' => self::hasFundsOfType(Fund::TYPE_SUBSIDIES),
                'digid' => $implementation->digidEnabled(),
                'digid_mandatory' => $implementation->digid_required ?? true,
                'communication_type' => ($implementation->informal_communication ?? false ? 'informal' : 'formal'),
                'settings' => array_merge($implementation->only([
                    'title', 'description', 'description_alignment', 'description_html',
                    'overlay_enabled', 'overlay_type',
                ]), [
                    'overlay_opacity' => min(max($implementation->overlay_opacity, 0), 100) / 100,
                    'banner_text_color' => $implementation->getBannerTextColor(),
                ]),
                'fronts' => $implementation->only([
                    'url_webshop', 'url_sponsor', 'url_provider', 'url_validator', 'url_app',
                ]),
                'map' => $implementation->only('lon', 'lat'),
                'banner' => $banner ? array_only((new MediaResource($banner))->toArray(request()), [
                    'dominant_color', 'ext', 'sizes', 'uid', 'is_bright',
                ]): null,
                'implementation_name' => $implementation->name,
                'products_hard_limit' => config('forus.features.dashboard.organizations.products.hard_limit'),
                'products_soft_limit' => config('forus.features.dashboard.organizations.products.soft_limit'),
                'pages' => $implementation->getPages(),
            ]);
        }

        return $config ?: [];
    }


    /**
     * @param string $type
     * @return bool
     */
    public static function hasFundsOfType(string $type): bool
    {
        return self::activeFundsQuery()->where('type', $type)->exists();
    }

    /**
     * @return Collection
     */
    private static function getPlatformMediaConfig(): Collection
    {
        return collect(MediaService::getMediaConfigs())->map(static function (
            MediaImageConfig $mediaConfig
        ) {
            return [
                'aspect_ratio' => $mediaConfig->getPreviewAspectRatio(),
                'size' => collect($mediaConfig->getPresets())->map(static function (
                    MediaPreset $mediaPreset
                ) {
                    return $mediaPreset instanceof MediaImagePreset ? [
                        $mediaPreset->width,
                        $mediaPreset->height,
                        $mediaPreset->preserve_aspect_ratio,
                    ] : null;
                })
            ];
        });
    }

    /**
     * @param array $options
     * @param Builder|null $query
     * @return Builder
     */
    public static function searchProviders(array $options, Builder $query = null): Builder
    {
        $query = $query ?: Organization::query();

        $query->whereHas('supplied_funds_approved', static function (Builder $builder) {
            $builder->whereIn('funds.id', self::activeFundsQuery()->pluck('funds.id'));
        });

        if ($business_type_id = array_get($options, 'business_type_id')) {
            $query->where('business_type_id', $business_type_id);
        }

        if ($organization_id = array_get($options, 'organization_id')) {
            $query->where('id', $organization_id);
        }

        if ($fund_id = array_get($options, 'fund_id')) {
            $query->whereHas('supplied_funds_approved', static function (Builder $builder) use ($fund_id) {
                $builder->where('funds.id', $fund_id);
            });
        }

        if ($q = array_get($options, 'q')) {
            $query->where(static function (Builder $builder) use ($q) {
                $like = '%' . $q . '%';
                $builder->where('name', 'LIKE', $like);

                $builder->orWhere(static function (Builder $builder) use ($like) {
                    $builder->where('email_public', true);
                    $builder->where('email', 'LIKE', $like);
                })->orWhere(static function (Builder $builder) use ($like) {
                    $builder->where('phone_public', true);
                    $builder->where('phone', 'LIKE', $like);
                })->orWhere(static function (Builder $builder) use ($like) {
                    $builder->where('website_public', true);
                    $builder->where('website', 'LIKE', $like);
                });

                $builder->orWhereHas('business_type.translations', static function (
                    Builder $builder
                ) use ($like) {
                    $builder->where('business_type_translations.name', 'LIKE', $like);
                });

                $builder->orWhereHas('offices', static function (
                    Builder $builder
                ) use ($like) {
                    $builder->where(static function (Builder $query) use ($like) {
                        $query->where(
                            'address', 'LIKE', $like
                        );
                    });
                });
            });
        }

        return $query->orderBy(
            array_get($options, 'order_by', 'created_at'),
            array_get($options, 'order_by_dir', 'desc'));
    }

    /**
     * @param string|null $key
     * @return EmailFrom
     */
    public static function emailFrom(?string $key = null): EmailFrom
    {
        if ($implementation = ($key ? self::byKey($key) : self::active())) {
            return $implementation->getEmailFrom();
        }

        return EmailFrom::createDefault();
    }

    /**
     * @return EmailFrom
     */
    public function getEmailFrom(): EmailFrom
    {
        return new EmailFrom($this);
    }

    /**
     * @return bool
     */
    public function isGeneral(): bool
    {
        return $this->key === self::KEY_GENERAL;
    }

    /**
     * @return Implementation
     */
    public static function general(): Implementation
    {
        return self::byKey(self::KEY_GENERAL);
    }

    /**
     * @return string
     * @noinspection PhpUnused
     */
    public function getDescriptionHtmlAttribute(): string
    {
        return resolve('markdown')->convertToHtml($this->description ?? '');
    }

    /**
     * @param array $pages
     * @return $this
     */
    public function updatePages(array $pages): self
    {
        foreach ($pages as $pageType => $pageData) {
            /** @var ImplementationPage $pageModel */
            $pageModel = $this->pages()->firstOrCreate([
                'page_type' => $pageType,
            ]);

            $pageModel->updateModel(array_merge(array_only($pageData, [
                'content', 'content_alignment', 'external', 'external_url',
            ]), in_array($pageType, ImplementationPage::TYPES_INTERNAL) ? [
                'external' => 0,
                'external_url' => null,
            ] : []))->appendMedia($pageData['media_uid'] ?? [], 'cms_media');
        }

        return $this;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection|Collection
     */
    private function getPages()
    {
        $pages = self::general()->pages;

        if (!$this->isGeneral()) {
            foreach (ImplementationPage::TYPES as $page_type) {
                $localPages = $this->pages->filter(function(ImplementationPage $page) use ($page_type) {
                    return $page->page_type === $page_type && (
                        $page->external ? $page->external_url : $page->content);
                });

                if ($localPages->count() > 0) {
                    $pageIndex = $pages->find($localPages->first());
                    $pages[$pageIndex] = $localPages->first();
                }
            }
        }

        return $pages->map(static function(ImplementationPage $page) {
            return array_merge($page->only('page_type', 'external', 'content_alignment'), [
                'content_html' => $page->external ? '' : $page->content_html,
                'external_url' => $page->external ? $page->external_url : '',
            ]);
        })->keyBy('page_type');
    }

    /**
     * @return ?string
     */
    private function getBannerTextColor(): ?string
    {
        if ($this->header_text_color == 'auto') {
            return $this->banner ? ($this->banner->is_dark ? 'bright' : 'dark') : 'dark';
        }

        return $this->header_text_color;
    }
}
