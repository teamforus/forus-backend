<?php

namespace App\Models;

use App\Http\Requests\BaseFormRequest;
use App\Http\Resources\AnnouncementResource;
use App\Http\Resources\ImplementationPageResource;
use App\Http\Resources\MediaResource;
use App\Models\Traits\ValidatesValues;
use App\Scopes\Builders\FundProviderQuery;
use App\Scopes\Builders\FundQuery;
use App\Scopes\Builders\OfficeQuery;
use App\Scopes\Builders\VoucherQuery;
use App\Searches\AnnouncementSearch;
use App\Services\DigIdService\DigIdException;
use App\Services\DigIdService\Models\DigIdSession;
use App\Services\DigIdService\Repositories\DigIdCgiRepo;
use App\Services\DigIdService\Repositories\DigIdSamlRepo;
use App\Services\DigIdService\Repositories\Interfaces\DigIdRepo;
use App\Services\Forus\Notification\EmailFrom;
use App\Services\MediaService\MediaImageConfig;
use App\Services\MediaService\MediaImagePreset;
use App\Services\MediaService\MediaPreset;
use App\Services\MediaService\MediaService;
use App\Services\MediaService\Models\Media;
use App\Services\MediaService\Traits\HasMedia;
use App\Services\TranslationService\Traits\HasOnDemandTranslations;
use App\Traits\HasMarkdownDescription;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Gate;

/**
 * App\Models\Implementation.
 *
 * @property int $id
 * @property int|null $organization_id
 * @property string $key
 * @property string $name
 * @property string|null $title
 * @property string|null $description
 * @property string $description_alignment
 * @property string|null $page_title_suffix
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
 * @property string|null $email_color
 * @property string|null $email_signature
 * @property bool $show_home_map
 * @property bool $show_home_products
 * @property bool $show_providers_map
 * @property bool $show_provider_map
 * @property bool $show_office_map
 * @property bool $show_voucher_map
 * @property bool $show_product_map
 * @property bool $show_privacy_checkbox
 * @property bool $show_terms_checkbox
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
 * @property string|null $digid_cgi_tls_key
 * @property string|null $digid_cgi_tls_cert
 * @property bool $pre_check_enabled
 * @property string $pre_check_title
 * @property string $pre_check_banner_title
 * @property string $pre_check_description
 * @property string $pre_check_banner_description
 * @property string|null $pre_check_banner_label
 * @property string $pre_check_banner_state
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read EloquentCollection|\App\Models\Announcement[] $announcements_webshop
 * @property-read int|null $announcements_webshop_count
 * @property-read Media|null $banner
 * @property-read Media|null $email_logo
 * @property-read EloquentCollection|\App\Models\FundConfig[] $fund_configs
 * @property-read int|null $fund_configs_count
 * @property-read EloquentCollection|\App\Models\Fund[] $funds
 * @property-read int|null $funds_count
 * @property-read string $description_html
 * @property-read EloquentCollection|\App\Models\ImplementationLanguage[] $implementation_languages
 * @property-read int|null $implementation_languages_count
 * @property-read EloquentCollection|\App\Models\Language[] $languages
 * @property-read int|null $languages_count
 * @property-read EloquentCollection|\App\Models\NotificationTemplate[] $mail_templates
 * @property-read int|null $mail_templates_count
 * @property-read EloquentCollection|Media[] $medias
 * @property-read int|null $medias_count
 * @property-read \App\Models\Organization|null $organization
 * @property-read \App\Models\ImplementationPage|null $page_accessibility
 * @property-read \App\Models\ImplementationPage|null $page_explanation
 * @property-read \App\Models\ImplementationPage|null $page_privacy
 * @property-read \App\Models\ImplementationPage|null $page_provider
 * @property-read \App\Models\ImplementationPage|null $page_terms_and_conditions
 * @property-read EloquentCollection|\App\Models\ImplementationPage[] $pages
 * @property-read int|null $pages_count
 * @property-read EloquentCollection|\App\Models\ImplementationPage[] $pages_public
 * @property-read int|null $pages_public_count
 * @property-read Media|null $pre_check_banner
 * @property-read EloquentCollection|\App\Models\PreCheck[] $pre_checks
 * @property-read int|null $pre_checks_count
 * @property-read EloquentCollection|\App\Models\PreCheckRecord[] $pre_checks_records
 * @property-read int|null $pre_checks_records_count
 * @property-read EloquentCollection|\App\Models\ImplementationSocialMedia[] $social_medias
 * @property-read int|null $social_medias_count
 * @method static Builder<static>|Implementation newModelQuery()
 * @method static Builder<static>|Implementation newQuery()
 * @method static Builder<static>|Implementation query()
 * @method static Builder<static>|Implementation whereAllowPerFundNotificationTemplates($value)
 * @method static Builder<static>|Implementation whereCreatedAt($value)
 * @method static Builder<static>|Implementation whereDescription($value)
 * @method static Builder<static>|Implementation whereDescriptionAlignment($value)
 * @method static Builder<static>|Implementation whereDigidASelectServer($value)
 * @method static Builder<static>|Implementation whereDigidAppId($value)
 * @method static Builder<static>|Implementation whereDigidCgiTlsCert($value)
 * @method static Builder<static>|Implementation whereDigidCgiTlsKey($value)
 * @method static Builder<static>|Implementation whereDigidConnectionType($value)
 * @method static Builder<static>|Implementation whereDigidEnabled($value)
 * @method static Builder<static>|Implementation whereDigidEnv($value)
 * @method static Builder<static>|Implementation whereDigidForusApiUrl($value)
 * @method static Builder<static>|Implementation whereDigidRequired($value)
 * @method static Builder<static>|Implementation whereDigidSamlContext($value)
 * @method static Builder<static>|Implementation whereDigidSharedSecret($value)
 * @method static Builder<static>|Implementation whereDigidSignUpAllowed($value)
 * @method static Builder<static>|Implementation whereDigidTrustedCert($value)
 * @method static Builder<static>|Implementation whereEmailColor($value)
 * @method static Builder<static>|Implementation whereEmailFromAddress($value)
 * @method static Builder<static>|Implementation whereEmailFromName($value)
 * @method static Builder<static>|Implementation whereEmailSignature($value)
 * @method static Builder<static>|Implementation whereHeaderTextColor($value)
 * @method static Builder<static>|Implementation whereId($value)
 * @method static Builder<static>|Implementation whereInformalCommunication($value)
 * @method static Builder<static>|Implementation whereKey($value)
 * @method static Builder<static>|Implementation whereLat($value)
 * @method static Builder<static>|Implementation whereLon($value)
 * @method static Builder<static>|Implementation whereName($value)
 * @method static Builder<static>|Implementation whereOrganizationId($value)
 * @method static Builder<static>|Implementation whereOverlayEnabled($value)
 * @method static Builder<static>|Implementation whereOverlayOpacity($value)
 * @method static Builder<static>|Implementation whereOverlayType($value)
 * @method static Builder<static>|Implementation wherePageTitleSuffix($value)
 * @method static Builder<static>|Implementation wherePreCheckBannerDescription($value)
 * @method static Builder<static>|Implementation wherePreCheckBannerLabel($value)
 * @method static Builder<static>|Implementation wherePreCheckBannerState($value)
 * @method static Builder<static>|Implementation wherePreCheckBannerTitle($value)
 * @method static Builder<static>|Implementation wherePreCheckDescription($value)
 * @method static Builder<static>|Implementation wherePreCheckEnabled($value)
 * @method static Builder<static>|Implementation wherePreCheckTitle($value)
 * @method static Builder<static>|Implementation whereShowHomeMap($value)
 * @method static Builder<static>|Implementation whereShowHomeProducts($value)
 * @method static Builder<static>|Implementation whereShowOfficeMap($value)
 * @method static Builder<static>|Implementation whereShowPrivacyCheckbox($value)
 * @method static Builder<static>|Implementation whereShowProductMap($value)
 * @method static Builder<static>|Implementation whereShowProviderMap($value)
 * @method static Builder<static>|Implementation whereShowProvidersMap($value)
 * @method static Builder<static>|Implementation whereShowTermsCheckbox($value)
 * @method static Builder<static>|Implementation whereShowVoucherMap($value)
 * @method static Builder<static>|Implementation whereTitle($value)
 * @method static Builder<static>|Implementation whereUpdatedAt($value)
 * @method static Builder<static>|Implementation whereUrlApp($value)
 * @method static Builder<static>|Implementation whereUrlProvider($value)
 * @method static Builder<static>|Implementation whereUrlSponsor($value)
 * @method static Builder<static>|Implementation whereUrlValidator($value)
 * @method static Builder<static>|Implementation whereUrlWebshop($value)
 * @mixin \Eloquent
 */
class Implementation extends BaseModel
{
    use HasMedia, HasMarkdownDescription, ValidatesValues, HasOnDemandTranslations;

    public const string KEY_GENERAL = 'general';

    public const string FRONTEND_WEBSHOP = 'webshop';
    public const string FRONTEND_SPONSOR_DASHBOARD = 'sponsor';
    public const string FRONTEND_PROVIDER_DASHBOARD = 'provider';
    public const string FRONTEND_VALIDATOR_DASHBOARD = 'validator';

    public const string FRONTEND_WEBSITE = 'website';
    public const string FRONTEND_PIN_CODE = 'pin_code-auth';

    public const string ME_APP_IOS = 'me_app-ios';
    public const string ME_APP_ANDROID = 'me_app-android';
    public const string ME_APP_DEPRECATED = 'app-me_app';

    public const array FRONTEND_KEYS = [
        self::FRONTEND_WEBSHOP,
        self::FRONTEND_SPONSOR_DASHBOARD,
        self::FRONTEND_PROVIDER_DASHBOARD,
        self::FRONTEND_VALIDATOR_DASHBOARD,
    ];

    protected $perPage = 20;

    /** @var self[] */
    protected static array $instances = [];

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
        'digid_cgi_tls_key', 'digid_cgi_tls_cert',
        'pre_check_enabled', 'pre_check_title', 'pre_check_banner_state', 'pre_check_banner_title',
        'pre_check_description', 'pre_check_banner_description', 'pre_check_banner_label',
        'page_title_suffix', 'show_privacy_checkbox', 'show_terms_checkbox',
    ];

    /**
     * @var string[]
     */
    protected $hidden = [
        'digid_enabled', 'digid_env', 'digid_app_id', 'digid_shared_secret',
        'digid_a_select_server',
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
        'currency_round' => 'boolean',
        'pre_check_enabled' => 'boolean',
        'show_privacy_checkbox' => 'boolean',
        'show_terms_checkbox' => 'boolean',
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
            'type' => 'implementation_banner',
        ]);
    }

    /**
     * Get fund banner
     * @return MorphOne
     * @noinspection PhpUnused
     */
    public function pre_check_banner(): MorphOne
    {
        return $this->morphOne(Media::class, 'mediable')->where([
            'type' => 'pre_check_banner',
        ]);
    }

    /**
     * @return HasMany
     */
    public function pre_checks(): HasMany
    {
        return $this->hasMany(PreCheck::class);
    }

    /**
     * @return HasMany
     */
    public function pre_checks_records(): HasMany
    {
        return $this->hasMany(PreCheckRecord::class);
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
     * Define a one-to-many relationship with ImplementationLanguage.
     *
     * @return HasMany
     * @noinspection PhpUnused
     */
    public function implementation_languages(): HasMany
    {
        return $this->hasMany(ImplementationLanguage::class);
    }

    /**
     * Define a many-to-many relationship with Language through the pivot table.
     *
     * @return BelongsToMany
     */
    public function languages(): BelongsToMany
    {
        return $this->belongsToMany(
            Language::class,
            'implementation_languages',
            'implementation_id',
            'language_id',
        );
    }

    /**
     * @return array|string|null
     */
    public static function activeKey(): array|string|null
    {
        return BaseFormRequest::createFromBase(request())->implementation_key();
    }

    /**
     * @return Implementation|null
     */
    public static function active(): ?Implementation
    {
        return self::findAndMemo(self::activeKey());
    }

    /**
     * @return Implementation
     */
    public static function general(): Implementation
    {
        return self::findAndMemo(self::KEY_GENERAL);
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
     * @param string|null $key
     * @return Implementation|null
     */
    public static function findAndMemo(?string $key): ?Implementation
    {
        return self::$instances[$key] ??= self::byKey($key);
    }

    /**
     * @return void
     */
    public static function clearMemo(): void
    {
        self::$instances = [];
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
     * @return HasMany
     */
    public function social_medias(): HasMany
    {
        return $this->hasMany(ImplementationSocialMedia::class);
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
     * @return Builder|Fund
     */
    public static function queryFundsByState(...$states): Builder|Fund
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
     * @param string $configKey
     * @return array
     */
    public static function platformConfig(string $configKey): array
    {
        if (!self::isValidKey(self::activeKey())) {
            abort(403, 'unknown_implementation_key');
        }

        $config = match ($configKey) {
            'ios' => Config::get('forus.features.ios'),
            'me_app' => Config::get('forus.features.me_app'),
            'website' => Config::get('forus.features.website'),
            'webshop' => Config::get('forus.features.webshop'),
            'android' => Config::get('forus.features.android'),
            'dashboard' => Config::get('forus.features.dashboard'),
            default => null,
        };

        if (!is_array($config)) {
            return [];
        }

        $implementation = self::active() ?? abort(403);
        $banner = $implementation->banner;
        $request = BaseFormRequest::createFromBase(request());
        $pages = ImplementationPageResource::queryCollection($implementation->pages_public())->toArray($request);

        return [
            ...$config,
            'media' => self::getPlatformMediaConfig(),
            'has_budget_funds' => self::hasFundsOfType(Fund::TYPE_BUDGET),
            'has_subsidy_funds' => self::hasFundsOfType(Fund::TYPE_SUBSIDIES),
            'has_reimbursements' => $implementation->hasReimbursements(),
            'has_payouts' => $implementation->hasPayouts(),
            'announcements' => AnnouncementResource::collection((new AnnouncementSearch([
                'client_type' => $request->client_type(),
                'implementation_id' => $implementation->id,
            ]))->query()->get())->toArray($request),
            'digid' => $implementation->digidEnabled(),
            'digid_sign_up_allowed' => $implementation->digid_sign_up_allowed,
            'digid_mandatory' => $implementation->digid_required ?? true,
            'digid_api_url' => rtrim($implementation->digid_forus_api_url ?: url('/'), '/') . '/api/v1',
            'communication_type' => $implementation->communicationType(),
            'settings' => [
                ...$implementation->only([
                    'description', 'description_alignment', 'overlay_enabled', 'overlay_type',
                ]),
                ...$implementation->translateColumns($implementation->only([
                    'title', 'description_html',
                ])),
                'overlay_opacity' => min(max($implementation->overlay_opacity, 0), 100) / 100,
                'banner_text_color' => $implementation->getBannerTextColor(),
            ],
            'fronts' => [
                ...$implementation->only([
                    'url_webshop', 'url_sponsor', 'url_provider', 'url_validator', 'url_app',
                ]),
                'url_sponsor_sign_up' => $implementation->urlSponsorDashboard('aanmelden'),
                'url_provider_sign_up' => $implementation->urlProviderDashboard('aanmelden'),
                'url_validator_sign_up' => $implementation->urlValidatorDashboard('aanmelden'),
            ],
            'map' => $implementation->only('lon', 'lat'),
            'banner' => $banner ? array_only((new MediaResource($banner))->toArray(request()), [
                'dominant_color', 'ext', 'sizes', 'uid', 'is_bright',
            ]) : null,
            'languages' => $implementation->getAvailableLanguages(),
            'implementation' => $implementation->translateColumns($implementation->only([
                'name'
            ])),
            'products_hard_limit' => config('forus.features.dashboard.organizations.products.hard_limit'),
            'products_soft_limit' => config('forus.features.dashboard.organizations.products.soft_limit'),
            // 'pages' => ImplementationPageResource::collection($implementation->pages_public->keyBy('page_type')),
            'pages' => Arr::keyBy($pages, 'page_type'),
            'social_medias' => $implementation->social_medias->map(fn (ImplementationSocialMedia $media) => $media->only([
                'url', 'type', 'title',
            ])),
            ...$implementation->isGeneral() ? [] : $implementation->getPreCheckFields(),
            ...$implementation->only([
                'show_home_map', 'show_home_products', 'show_providers_map', 'show_provider_map',
                'show_office_map', 'show_voucher_map', 'show_product_map', 'page_title_suffix',
                'show_privacy_checkbox', 'show_terms_checkbox',
            ]),
        ];
    }

    /**
     * @return array
     */
    public function getAvailableLanguages(): array
    {
        $languages = Language::getAllLanguages();

        if (!$this->organization?->allow_translations || !$this?->organization->translations_enabled) {
            return $languages->where('base', true)->values()->toArray();
        }

        return $languages->filter(function (Language $language) {
            return $language->base || $this->languages->contains('id', $language->id);
        })->map(fn (Language $language) => $language->only([
            'id', 'locale', 'name', 'base',
        ]))->values()->toArray();
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
     * @return bool
     */
    public function hasPayouts(): bool
    {
        $payoutFunds = self::queryFunds()->whereRelation('fund_config', [
            'outcome_type' => FundConfig::OUTCOME_TYPE_PAYOUT,
        ]);

        return $this->organization?->allow_payouts && $payoutFunds->exists();
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

        $query->whereHas('fund_providers', static function (Builder $builder) {
            $builder->whereIn('fund_id', self::activeFundsQuery()->select('id'));
            FundProviderQuery::whereApproved($builder);
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
            $query->whereRelation('supplied_funds', 'funds.id', $fund_id);
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
            array_get($options, 'order_dir', 'desc'),
        );
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

            if ($voucher = $fund->makeVoucher($identity)) {
                $vouchers[] = $voucher;
            }

            return array_merge($vouchers, $fund->makeFundFormulaProductVouchers($identity));
        }, []);
    }

    /**
     * @param string|null $frontend
     * @return string|null
     */
    public function makePreferencesLink(?string $frontend): ?string
    {
        $uri = '/preferences/notifications';

        if (!in_array($frontend, static::FRONTEND_KEYS)) {
            return null;
        }

        if ($frontend == self::FRONTEND_WEBSHOP) {
            return !$this->isGeneral() ? $this->urlWebshop($uri) : null;
        }

        return Implementation::general()->urlFrontend($frontend, $uri);
    }

    /**
     * @return array
     */
    private function getPreCheckFields(): array
    {
        return [
            'pre_check_banner' => new MediaResource($this->pre_check_banner),
            'pre_check_enabled' => $this->pre_check_enabled,
            'pre_check_banner_state' => $this->pre_check_banner_state,
            ...$this->translateColumns($this->only([
                'pre_check_title', 'pre_check_description', 'pre_check_banner_title',
                'pre_check_banner_description', 'pre_check_banner_label',
            ])),
        ];
    }

    /**
     * @param array $pre_checks
     * @return void
     */
    public function syncPreChecks(array $pre_checks): void
    {
        $this->pre_checks()
            ->whereNotIn('id', array_filter(Arr::pluck($pre_checks, 'id')))
            ->delete();

        foreach ($pre_checks as $order => $preCheck) {
            $preCheckData = [
                ...Arr::only($preCheck, ['title', 'title_short', 'description', 'default']),
                'order' => $order,
            ];

            /** @var PreCheck $pre_check */
            if ($pre_check = $this->pre_checks()->find($preCheck['id'] ?? null)) {
                $pre_check->update($preCheckData);
            } else {
                $pre_check = $this->pre_checks()->create($preCheckData);
            }

            foreach (Arr::get($preCheck, 'record_types', []) as $order2 => $preCheckRecordType) {
                /** @var PreCheckRecord $preCheckRecord */
                $preCheckRecord = $this->pre_checks_records()->updateOrCreate([
                    'record_type_key' => $preCheckRecordType['record_type_key'],
                ], [
                    'order' => $order2,
                    'pre_check_id' => $pre_check->id,
                    ...Arr::only($preCheckRecordType, ['title', 'title_short', 'description'])
                ]);

                foreach (Arr::get($preCheckRecordType, 'record_settings', []) as $recordSetting) {
                    $preCheckRecord->settings()->updateOrCreate([
                        'pre_check_record_id' => $preCheckRecord->id,
                        'fund_id' => $recordSetting['fund_id'],
                    ], Arr::only($recordSetting, ['description', 'impact_level', 'is_knock_out']));
                }
            }
        }

        /** @var ?PreCheck $defaultPreCheck */
        if ($defaultPreCheck = $this->pre_checks()->where('default', true)->first()) {
            $this->pre_checks()->where('id', '!=', $defaultPreCheck->id)->update([
                'default' => false,
            ]);
        }
    }
}
