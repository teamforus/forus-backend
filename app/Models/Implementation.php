<?php

namespace App\Models;

use App\Http\Requests\BaseFormRequest;
use App\Http\Resources\AnnouncementResource;
use App\Http\Resources\ImplementationPageResource;
use App\Http\Resources\MediaResource;
use App\Models\Traits\ValidatesValues;
use App\Scopes\Builders\FundQuery;
use App\Scopes\Builders\OfficeQuery;
use App\Services\DigIdService\Models\DigIdSession;
use App\Services\DigIdService\Repositories\DigIdCgiRepo;
use App\Services\DigIdService\Repositories\DigIdSamlRepo;
use App\Services\DigIdService\DigIdException;
use App\Scopes\Builders\VoucherQuery;
use App\Services\DigIdService\Repositories\Interfaces\DigIdRepo;
use App\Services\Forus\Notification\EmailFrom;
use App\Services\MediaService\MediaImageConfig;
use App\Services\MediaService\MediaImagePreset;
use App\Services\MediaService\MediaPreset;
use App\Services\MediaService\MediaService;
use App\Services\MediaService\Models\Media;
use App\Services\MediaService\Traits\HasMedia;
use Illuminate\Config\Repository;
use Illuminate\Contracts\Foundation\Application;
use App\Traits\HasMarkdownDescription;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Facades\Gate;

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
 * @property string|null $productboard_api_key
 * @property string|null $email_from_address
 * @property string|null $email_from_name
 * @property string|null $email_color
 * @property string|null $email_signature
 * @property bool $show_home_map
 * @property bool $show_home_products
 * @property bool $show_providers_map
 * @property bool $show_provider_map
 * @property bool $show_office_map
 * @property bool $show_voucher_map
 * @property bool $show_product_map
 * @property bool $allow_per_fund_notification_templates
 * @property bool $digid_enabled
 * @property bool $digid_required
 * @property bool $digid_sign_up_allowed
 * @property string $digid_connection_type
 * @property array|null $digid_saml_context
 * @property string $digid_env
 * @property string|null $digid_app_id
 * @property string|null $digid_shared_secret
 * @property string|null $digid_a_select_server
 * @property string|null $digid_forus_api_url
 * @property string|null $digid_trusted_cert
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Announcement> $announcements_webshop
 * @property-read int|null $announcements_webshop_count
 * @property-read Media|null $banner
 * @property-read Media|null $email_logo
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\FundConfig> $fund_configs
 * @property-read int|null $fund_configs_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Fund> $funds
 * @property-read int|null $funds_count
 * @property-read string $description_html
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\NotificationTemplate> $mail_templates
 * @property-read int|null $mail_templates_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Media> $medias
 * @property-read int|null $medias_count
 * @property-read \App\Models\Organization|null $organization
 * @property-read \App\Models\ImplementationPage|null $page_accessibility
 * @property-read \App\Models\ImplementationPage|null $page_explanation
 * @property-read \App\Models\ImplementationPage|null $page_privacy
 * @property-read \App\Models\ImplementationPage|null $page_provider
 * @property-read \App\Models\ImplementationPage|null $page_terms_and_conditions
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ImplementationPage> $pages
 * @property-read int|null $pages_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ImplementationPage> $pages_public
 * @property-read int|null $pages_public_count
 * @method static Builder|Implementation newModelQuery()
 * @method static Builder|Implementation newQuery()
 * @method static Builder|Implementation query()
 * @method static Builder|Implementation whereAllowPerFundNotificationTemplates($value)
 * @method static Builder|Implementation whereCreatedAt($value)
 * @method static Builder|Implementation whereDescription($value)
 * @method static Builder|Implementation whereDescriptionAlignment($value)
 * @method static Builder|Implementation whereDigidASelectServer($value)
 * @method static Builder|Implementation whereDigidAppId($value)
 * @method static Builder|Implementation whereDigidConnectionType($value)
 * @method static Builder|Implementation whereDigidEnabled($value)
 * @method static Builder|Implementation whereDigidEnv($value)
 * @method static Builder|Implementation whereDigidForusApiUrl($value)
 * @method static Builder|Implementation whereDigidRequired($value)
 * @method static Builder|Implementation whereDigidSamlContext($value)
 * @method static Builder|Implementation whereDigidSharedSecret($value)
 * @method static Builder|Implementation whereDigidSignUpAllowed($value)
 * @method static Builder|Implementation whereDigidTrustedCert($value)
 * @method static Builder|Implementation whereEmailColor($value)
 * @method static Builder|Implementation whereEmailFromAddress($value)
 * @method static Builder|Implementation whereEmailFromName($value)
 * @method static Builder|Implementation whereEmailSignature($value)
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
 * @method static Builder|Implementation whereProductboardApiKey($value)
 * @method static Builder|Implementation whereShowHomeMap($value)
 * @method static Builder|Implementation whereShowHomeProducts($value)
 * @method static Builder|Implementation whereShowOfficeMap($value)
 * @method static Builder|Implementation whereShowProductMap($value)
 * @method static Builder|Implementation whereShowProviderMap($value)
 * @method static Builder|Implementation whereShowProvidersMap($value)
 * @method static Builder|Implementation whereShowVoucherMap($value)
 * @method static Builder|Implementation whereTitle($value)
 * @method static Builder|Implementation whereUpdatedAt($value)
 * @method static Builder|Implementation whereUrlApp($value)
 * @method static Builder|Implementation whereUrlProvider($value)
 * @method static Builder|Implementation whereUrlSponsor($value)
 * @method static Builder|Implementation whereUrlValidator($value)
 * @method static Builder|Implementation whereUrlWebshop($value)
 * @mixin \Eloquent
 */
class Implementation extends BaseModel
{
    use HasMedia, HasMarkdownDescription, ValidatesValues;

    public const KEY_GENERAL = 'general';

    public const FRONTEND_WEBSHOP = 'webshop';
    public const FRONTEND_SPONSOR_DASHBOARD = 'sponsor';
    public const FRONTEND_PROVIDER_DASHBOARD = 'provider';
    public const FRONTEND_VALIDATOR_DASHBOARD = 'validator';

    public const FRONTEND_WEBSITE = 'website';
    public const FRONTEND_PIN_CODE = 'pin_code-auth';

    public const ME_APP_IOS = 'me_app-ios';
    public const ME_APP_ANDROID = 'me_app-android';
    public const ME_APP_DEPRECATED = 'app-me_app';

    public const DEPRECATED_FRONTEND_GENERAL = 'general';

    public const FRONTEND_KEYS = [
        self::FRONTEND_WEBSHOP,
        self::FRONTEND_SPONSOR_DASHBOARD,
        self::FRONTEND_PROVIDER_DASHBOARD,
        self::FRONTEND_VALIDATOR_DASHBOARD,
    ];

    protected $perPage = 20;
    protected static ?Implementation $generalModel = null;
    protected static ?Implementation $activeModel = null;

    /**
     * @var string[]
     */
    protected $fillable = [
        'id', 'key', 'name', 'url_webshop', 'url_sponsor', 'url_provider',
        'url_validator', 'lon', 'lat', 'email_from_address', 'email_from_name',
        'title', 'description', 'description_alignment', 'informal_communication',
        'digid_app_id', 'digid_shared_secret', 'digid_a_select_server', 'digid_enabled',
        'overlay_enabled', 'overlay_type', 'overlay_opacity', 'header_text_color',
        'show_home_map', 'show_home_products', 'show_providers_map', 'show_provider_map',
        'show_office_map', 'show_voucher_map', 'show_product_map', 'email_color', 'email_signature',
    ];

    /**
     * @var string[]
     */
    protected $hidden = [
        'digid_enabled', 'digid_env', 'digid_app_id', 'digid_shared_secret',
        'digid_a_select_server', 'productboard_api_key'
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
        'digid_sign_up_allowed' => 'boolean',
        'informal_communication' => 'boolean',
        'show_home_map' => 'boolean',
        'show_home_products' => 'boolean',
        'show_providers_map' => 'boolean',
        'show_provider_map' => 'boolean',
        'show_office_map' => 'boolean',
        'digid_saml_context' => 'json',
        'show_voucher_map' => 'boolean',
        'show_product_map' => 'boolean',
        'allow_per_fund_notification_templates' => 'boolean',
    ];

    /**
     * @return HasMany
     * @noinspection PhpUnused
     */
    public function pages(): HasMany
    {
        return $this->hasMany(ImplementationPage::class);
    }

    /**
     * @return HasMany
     * @noinspection PhpUnused
     */
    public function pages_public(): HasMany
    {
        return $this->hasMany(ImplementationPage::class)->where([
            'implementation_pages.state' => ImplementationPage::STATE_PUBLIC,
        ]);
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
     * @return HasMany
     * @noinspection PhpUnused
     */
    public function mail_templates(): HasMany
    {
        return $this->hasMany(NotificationTemplate::class);
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
     * @noinspection PhpUnused
     */
    public function banner(): MorphOne
    {
        return $this->morphOne(Media::class, 'mediable')->where([
            'type' => 'implementation_banner'
        ]);
    }

    /**
     * Get fund banner
     * @return MorphOne
     * @noinspection PhpUnused
     */
    public function email_logo(): MorphOne
    {
        return $this->morphOne(Media::class, 'mediable')->where([
            'type' => 'email_logo',
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
     * @return HasMany
     */
    public function announcements_webshop(): HasMany
    {
        return $this->hasMany(Announcement::class)->where('scope', 'webshop');
    }

    /**
     * @return array|string|null
     */
    public static function activeKey(): array|string|null
    {
        return request()->header('Client-Key', self::KEY_GENERAL);
    }

    /**
     * @return Implementation|null
     */
    public static function active(): ?Implementation
    {
        return static::$activeModel ?: static::$activeModel = self::byKey(self::activeKey());
    }

    /**
     * @return Implementation
     */
    public static function general(): Implementation
    {
        return static::$generalModel ?: static::$generalModel = self::byKey(self::KEY_GENERAL);
    }

    /**
     * @return bool
     */
    public function isGeneral(): bool
    {
        return $this->key === self::KEY_GENERAL;
    }

    /**
     * @param $key
     * @return Implementation|null
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
     * @param ...$states
     * @return Builder
     */
    public static function queryFundsByState(...$states): Builder
    {
        return self::queryFunds()->whereIn('state', is_array($states[0] ?? null) ? $states[0] : $states);
    }

    /**
     * @return Builder|Fund
     */
    public static function queryFunds(): Builder|Fund
    {
        $query = FundQuery::whereIsConfiguredByForus(Fund::query());

        if (self::activeKey() !== self::KEY_GENERAL) {
            $query->whereRelation('fund_config.implementation', 'key', self::activeKey());
        }

        return $query;
    }

    /**
     * @return Collection|Fund[]
     */
    public static function activeFunds(): Collection|Arrayable
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
        if ($this->digid_connection_type == DigIdSession::CONNECTION_TYPE_SAML) {
            return $this->digid_enabled && !empty($this->getDigidSamlContext());
        }

        return
            $this->digid_enabled &&
            !empty($this->digid_app_id) &&
            !empty($this->digid_shared_secret) &&
            !empty($this->digid_a_select_server);
    }

    /**
     * @return DigIdRepo|null
     * @throws DigIdException
     */
    public function getDigid(): ?DigIdRepo
    {
        return match ($this->digid_connection_type) {
            DigIdSession::CONNECTION_TYPE_SAML => (new DigIdSamlRepo($this->getDigidSamlContext())),
            DigIdSession::CONNECTION_TYPE_CGI => (new DigIdCgiRepo($this->digid_env))
                ->setAppId($this->digid_app_id)
                ->setSharedSecret($this->digid_shared_secret)
                ->setASelectServer($this->digid_a_select_server)
                ->setTrustedCertificate($this->digid_trusted_cert),
            default => null,
        };
    }

    /**
     * @return array|null
     */
    protected function getDigidSamlContext(): ?array
    {
        return $this->digid_saml_context ?: Implementation::general()->digid_saml_context;
    }

    /**
     * @param string $frontend
     * @param string $uri
     * @param array $params
     * @return string|null
     */
    public function urlFrontend(string $frontend, string $uri = '', array $params = []): ?string
    {
        return match ($frontend) {
            'webshop' => $this->urlWebshop($uri, $params),
            'sponsor' => $this->urlSponsorDashboard($uri, $params),
            'provider' => $this->urlProviderDashboard($uri, $params),
            'validator' => $this->urlValidatorDashboard($uri, $params),
            default => null,
        };
    }

    /**
     * @param string $url
     * @param array $getParams
     * @return string
     */
    protected function buildFrontendUrl(string $url, array $getParams = []): string
    {
        return implode('?', array_filter([$url, http_build_query($getParams)]));
    }

    /**
     * @param string $uri
     * @param array $params
     * @return string
     */
    public function urlWebshop(string $uri = "/", array $params = []): string
    {
        return $this->buildFrontendUrl(http_resolve_url($this->url_webshop, $uri), $params);
    }

    /**
     * @param string $uri
     * @param array $params
     * @return string
     */
    public function urlSponsorDashboard(string $uri = "/", array $params = []): string
    {
        return $this->buildFrontendUrl(http_resolve_url($this->url_sponsor, $uri), $params);
    }

    /**
     * @param string $uri
     * @param array $params
     * @return string
     */
    public function urlProviderDashboard(string $uri = "/", array $params = []): string
    {
        return $this->buildFrontendUrl(http_resolve_url($this->url_provider, $uri), $params);
    }

    /**
     * @param string $uri
     * @param array $params
     * @return string
     */
    public function urlValidatorDashboard(string $uri = "/", array $params = []): string
    {
        return $this->buildFrontendUrl(http_resolve_url($this->url_validator, $uri), $params);
    }

    /**
     * @return string
     */
    public function communicationType(): string
    {
        return $this->informal_communication ? 'informal' : 'formal';
    }

    /**
     * @param string $value
     * @return array|Repository|Application|mixed|void
     */
    public static function platformConfig(string $value)
    {
        if (!self::isValidKey(self::activeKey())) {
            abort(403, 'unknown_implementation_key');
        }

        $ver = request()->input('ver');

        if ($ver && self::validateValueStatic($ver, 'alpha_num')->fails()) {
            abort(403);
        }

        $config = config('forus.features.' . $value . ($ver ? '.' . $ver : ''));

        if (is_array($config)) {
            $implementation = self::active() ?? abort(403);
            $banner = $implementation->banner;

            $request = BaseFormRequest::createFromGlobals();
            $announcements = Announcement::search($request)->get();
            $pages = ImplementationPageResource::queryCollection($implementation->pages_public())->toArray($request);

            $config = array_merge($config, [
                'media' => self::getPlatformMediaConfig(),
                'has_budget_funds' => self::hasFundsOfType(Fund::TYPE_BUDGET),
                'has_subsidy_funds' => self::hasFundsOfType(Fund::TYPE_SUBSIDIES),
                'has_reimbursements' => $implementation->hasReimbursements(),
                'announcements' => AnnouncementResource::collection($announcements)->toArray($request),
                'digid' => $implementation->digidEnabled(),
                'digid_sign_up_allowed' => $implementation->digid_sign_up_allowed,
                'digid_mandatory' => $implementation->digid_required ?? true,
                'digid_api_url' => rtrim($implementation->digid_forus_api_url ?: url('/'), '/') . '/api/v1',
                'communication_type' => $implementation->communicationType(),
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
                // 'pages' => ImplementationPageResource::collection($implementation->pages_public->keyBy('page_type')),
                'pages' => Arr::keyBy($pages, 'page_type'),
                'has_productboard_integration' => !empty(resolve('productboard')),
            ], $implementation->only(
                'show_home_map', 'show_home_products', 'show_providers_map', 'show_provider_map',
                'show_office_map', 'show_voucher_map', 'show_product_map',
            ));
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
     * @return bool
     */
    public function hasReimbursements(): bool
    {
        return self::queryFunds()->whereRelation('fund_config', [
            'allow_reimbursements' => true,
        ])->exists();
    }

    /**
     * @return array
     */
    private static function getPlatformMediaConfig(): array
    {
        return array_map(function(MediaImageConfig $config) {
            $sizes = array_filter($config->getPresets(), function(MediaPreset $mediaPreset) {
                return get_class($mediaPreset) === MediaImagePreset::class;
            });

            $sizes = array_map(fn(MediaImagePreset $preset) => [
                $preset->width,
                $preset->height,
                $preset->preserve_aspect_ratio,
            ], $sizes);

            return [
                'aspect_ratio' => $config->getPreviewAspectRatio(),
                'size' => $sizes,
            ];
        }, MediaService::getMediaConfigs());
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

        if ($product_category_id = array_get($options, 'product_category_id')) {
            $query->whereHas('products', function (Builder $builder) use ($product_category_id) {
                $builder->whereIn('id', Product::search(compact('product_category_id'))->select('id'));
            });
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

        if (array_get($options, 'postcode') && array_get($options, 'distance')) {
            $geocodeService = resolve('geocode_api');
            $location = $geocodeService->getLocation(array_get($options, 'postcode') . ', Netherlands');

            $query->whereHas('offices', static function (Builder $builder) use ($location, $options) {
                OfficeQuery::whereDistance($builder, (int) array_get($options, 'distance'), [
                    'lat' => $location ? $location['lat'] : 0,
                    'lng' => $location ? $location['lng'] : 0,
                ]);
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
     * @return ?string
     */
    private function getBannerTextColor(): ?string
    {
        if ($this->header_text_color == 'auto') {
            return $this->banner ? ($this->banner->is_dark ? 'bright' : 'dark') : 'dark';
        }

        return $this->header_text_color;
    }

    /**
     * @param array $attributes
     * @param bool $replace
     * @return Announcement
     */
    public function addWebshopAnnouncement(array $attributes, bool $replace = false): Announcement
    {
        if ($replace) {
            $this->announcements_webshop()->delete();
        }

        /** @var Announcement $announcement */
        $announcement = $this->announcements_webshop()->firstOrCreate([], [
            'active' => false,
            'scope' => 'webshop',
        ]);

        $announcement->update(Arr::only($attributes, [
            'type', 'title', 'description', 'expire_at', 'active',
        ]));

        return $announcement;
    }

    /**
     * @return string|null
     */
    public function getProductboardApiKey(): ?string
    {
        return $this->productboard_api_key ?: Implementation::general()->productboard_api_key;
    }

    /**
     * @param Identity $identity
     * @return array|Voucher[]
     */
    public function makeVouchersInApplicableFunds(Identity $identity): array
    {
        $funds = FundQuery::whereIsInternalConfiguredAndActive($this->funds())
            ->whereNotIn('funds.id', VoucherQuery::whereNotExpired($identity->vouchers()->select('fund_id')))
            ->get();

        return $funds->reduce(function(array $vouchers, Fund $fund) use ($identity) {
            if (Gate::forUser($identity)->denies('apply', [$fund, 'apply'])) {
                return $vouchers;
            }

            if ($voucher = $fund->makeVoucher($identity->address)) {
                $vouchers[] = $voucher;
            }

            return array_merge($vouchers, $fund->makeFundFormulaProductVouchers($identity->address));
        }, []);
    }
}
