<?php

namespace App\Models;

use App\Services\DigIdService\Repositories\DigIdRepo;
use App\Services\Forus\Notification\EmailFrom;
use App\Services\MediaService\MediaImageConfig;
use App\Services\MediaService\MediaImagePreset;
use App\Services\MediaService\MediaPreset;
use App\Services\MediaService\MediaService;
use \Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Http\Request;

/**
 * App\Models\Implementation
 *
 * @property int $id
 * @property string $key
 * @property string $name
 * @property string|null $title
 * @property string|null $description
 * @property string|null $more_info_url
 * @property string|null $description_steps
 * @property bool $has_more_info_url
 * @property string $url_webshop
 * @property string $url_sponsor
 * @property string $url_provider
 * @property string $url_validator
 * @property string $url_app
 * @property float|null $lon
 * @property float|null $lat
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
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\FundConfig[] $fund_configs
 * @property-read int|null $fund_configs_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Fund[] $funds
 * @property-read int|null $funds_count
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Implementation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Implementation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Implementation query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Implementation whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Implementation whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Implementation whereDescriptionSteps($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Implementation whereDigidASelectServer($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Implementation whereDigidAppId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Implementation whereDigidEnabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Implementation whereDigidEnv($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Implementation whereDigidRequired($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Implementation whereDigidSharedSecret($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Implementation whereEmailFromAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Implementation whereEmailFromName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Implementation whereHasMoreInfoUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Implementation whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Implementation whereKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Implementation whereLat($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Implementation whereLon($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Implementation whereMoreInfoUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Implementation whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Implementation whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Implementation whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Implementation whereUrlApp($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Implementation whereUrlProvider($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Implementation whereUrlSponsor($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Implementation whereUrlValidator($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Implementation whereUrlWebshop($value)
 * @mixin \Eloquent
 */
class Implementation extends Model
{
    protected $perPage = 20;

    /**
     * @var string[]
     */
    protected $fillable = [
        'id', 'key', 'name', 'url_webshop', 'url_sponsor', 'url_provider',
        'url_validator', 'lon', 'lat', 'email_from_address', 'email_from_name',
        'title', 'description', 'has_more_info_url', 'more_info_url', 'description_steps',
        'digid_app_id', 'digid_shared_secret', 'digid_a_select_server', 'digid_enabled'
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
        'digid_enabled' => 'boolean',
        'digid_required' => 'boolean',
        'has_more_info_url' => 'boolean',
    ];

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

    /**
     * @return HasManyThrough
     */
    public function funds(): HasManyThrough {
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
     * @return array|string|null
     */
    public static function activeKey() {
        return request()->header('Client-Key', 'general');
    }

    /**
     * @return Collection
     */
    public static function active(): Collection
    {
        return self::byKey(self::activeKey());
    }

    /**
     * @param $key
     * @return Collection
     */
    public static function byKey($key): Collection
    {
        if ($key === 'general') {
            return collect(self::general_urls());
        }

        return collect(self::query()->where(compact('key'))->first());
    }

    /**
     * @param $key
     * @return Implementation|null
     */
    public static function findModelByKey($key): ?Implementation
    {
        /** @var Implementation|null $implementation */
        $implementation = self::query()->where(compact('key'))->first();
        return $implementation;
    }

    /**
     * @return Implementation|null
     */
    public static function activeModel(): ?Implementation
    {
        return self::findModelByKey(self::activeKey());
    }

    public static function general_urls(): array
    {
        return [
            'url_webshop'   => config('forus.front_ends.webshop'),
            'url_sponsor'   => config('forus.front_ends.panel-sponsor'),
            'url_provider'  => config('forus.front_ends.panel-provider'),
            'url_validator' => config('forus.front_ends.panel-validator'),
            'url_website'   => config('forus.front_ends.website-default'),
            'url_app'       => config('forus.front_ends.landing-app'),
            'lon'           => config('forus.front_ends.map.lon'),
            'lat'           => config('forus.front_ends.map.lat')
        ];
    }

    /**
     * @param $key
     * @return bool
     */
    public static function isValidKey($key): bool {
        return self::implementationKeysAvailable()->search($key) !== false;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function fund_configs(): HasMany {
        return $this->hasMany(FundConfig::class);
    }

    /**
     * @return EloquentBuilder
     */
    public static function activeFundsQuery() {
        return self::queryFundsByState('active');
    }

    /**
     * @param $states
     * @return Fund|EloquentBuilder|QueryBuilder
     */
    public static function queryFundsByState($states) {
        $states = (array) $states;

        if (self::activeKey() === 'general') {
            return Fund::query()->has('fund_config')->whereIn('state', $states);
        }

        return Fund::query()->whereIn('id', static function(QueryBuilder $query) {
            $query->select('fund_id')->from('fund_configs')->where([
                'implementation_id' => Implementation::where([
                    'key' => self::activeKey()
                ])->pluck('id')->first()
            ]);
        })->whereIn('state', $states);
    }

    /**
     * @return Collection
     */
    public static function activeFunds(): Collection {
        return self::activeFundsQuery()->get();
    }

    /**
     * @return Collection
     */
    public static function implementationKeysAvailable(): Collection {
        return self::query()->pluck('key')->merge([
            'general'
        ]);
    }

    /**
     * @return Collection
     */
    public static function keysAvailable (): Collection {
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
    public function digidEnabled(): bool {
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
    public function getDigid(): DigIdRepo {
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
     * @return mixed|string|null
     */
    public function urlFrontend(string $frontend, string $uri = '') {
        switch ($frontend) {
            case 'webshop': return $this->urlWebshop($uri);
            case 'sponsor': return $this->urlSponsorDashboard($uri);
            case 'provider': return $this->urlProviderDashboard($uri);
            case 'validator': return $this->urlValidatorDashboard($uri);
        }
        return null;
    }

    /**
     * @param string $uri
     * @return mixed|string
     */
    public function urlWebshop(string $uri = "/")
    {
        return http_resolve_url($this->url_webshop ?? env('WEB_SHOP_GENERAL_URL'), $uri);
    }

    /**
     * @param string $uri
     * @return mixed|string
     */
    public function urlSponsorDashboard(string $uri = "/")
    {
        return http_resolve_url($this->url_sponsor ?? env('PANEL_SPONSOR_URL'), $uri);
    }

    /**
     * @param string $uri
     * @return mixed|string
     */
    public function urlProviderDashboard(string $uri = "/")
    {
        return http_resolve_url($this->url_provider ?? env('PANEL_PROVIDER_URL'), $uri);
    }

    /**
     * @param string $uri
     * @return string
     */
    public function urlValidatorDashboard(string $uri = "/"): string {
        return http_resolve_url($this->url_validator ?? env('PANEL_VALIDATOR_URL'), $uri);
    }

    /**
     * @return bool
     */
    public function autoValidationEnabled(): bool {
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
    public static function platformConfig($value) {
        if (!self::isValidKey(self::activeKey())) {
            return abort(403, 'unknown_implementation_key');
        }

        $ver = request()->input('ver');

        if (preg_match('/[^a-z_\-0-9]/i', $value) || preg_match('/[^a-z_\-0-9]/i', $ver)) {
            abort(403);
        }

        $config = config('forus.features.' . $value . ($ver ? '.' . $ver : ''));

        if (is_array($config)) {
            $implementation = self::active();
            $implementationModel = self::activeModel();

            $config = array_merge($config, [
                'media' => self::getPlatformMediaConfig(),
                'has_budget_funds' => self::hasFundsOfType(Fund::TYPE_BUDGET),
                'has_subsidy_funds' => self::hasFundsOfType(Fund::TYPE_SUBSIDIES),
                'digid' => $implementationModel ? $implementationModel->digidEnabled() : false,
                'digid_mandatory' => $implementationModel ? $implementationModel->digid_required : true,
                'auto_validation' => $implementationModel && $implementationModel->autoValidationEnabled(),
                'settings' => self::getPlatformSettingsConfig($implementation),
                'fronts' => $implementation->only([
                    'url_webshop', 'url_sponsor', 'url_provider', 'url_validator', 'url_app'
                ]),
                'map' => [
                    'lon' => (float) ($implementation['lon'] ?? config('forus.front_ends.map.lon')),
                    'lat' => (float) ($implementation['lat'] ?? config('forus.front_ends.map.lat')),
                ],
                'implementation_name' => $implementation->get('name') ?: 'general',
            ]);
        }

        return $config ?: [];
    }


    /**
     * @param string $type
     * @return bool
     */
    public static function hasFundsOfType(string $type): bool {
        return self::activeFundsQuery()->where([
            'type' => $type
        ])->exists();
    }

    /**
     * @param Collection $implementation
     * @return array
     */
    private static function getPlatformSettingsConfig($implementation): array {
        return array_merge($implementation->only([
            'title', 'description', 'has_more_info_url',
            'more_info_url', 'description_steps',
        ])->toArray(), [
            'description_html' => resolve('markdown')->convertToHtml(
                $implementation['description'] ?? ''
            ),
            'description_steps_html' => resolve('markdown')->convertToHtml(
                $implementation['description_steps'] ?? ''
            ),
        ]);
    }

    /**
     * @return Collection
     */
    private static function getPlatformMediaConfig(): Collection
    {
        return collect(MediaService::getMediaConfigs())->map(static function(
            MediaImageConfig $mediaConfig
        ) {
            return [
                'aspect_ratio' => $mediaConfig->getPreviewAspectRatio(),
                'size' => collect($mediaConfig->getPresets())->map(static function(
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
     * @param Request $request
     * @return Organization|EloquentBuilder
     */
    public static function searchProviders(Request $request) {
        /** @var Builder $query */
        $query = Organization::query();
        $activeModel = self::activeModel();

        if ($activeModel && self::activeKey() !== 'general') {
            $funds = $activeModel->funds()->where([
                'state' => Fund::STATE_ACTIVE
            ])->pluck('fund_id');

            $query->whereHas('supplied_funds_approved', static function(
                EloquentBuilder $builder
            ) use ($funds) {
                $builder->whereIn('funds.id', $funds);
            });
        } else {
            $query->whereHas('supplied_funds_approved');
        }

        if ($request->has('business_type_id') && (
            $business_type = $request->input('business_type_id'))
        ) {
            $query->whereHas('business_type', static function(
                EloquentBuilder $builder
            ) use ($business_type) {
                $builder->where('id', $business_type);
            });
        }

        if ($request->has('fund_id') && (
            $fund_id = $request->input('fund_id'))
        ) {
            $query->whereHas('supplied_funds_approved', static function(
                EloquentBuilder $builder
            ) use ($fund_id) {
                $builder->where('funds.id', $fund_id);
            });
        }

        if ($request->has('q') && ($q = $request->input('q'))) {
            $query->where(static function(EloquentBuilder $builder) use ($q) {
                $like = '%' . $q . '%';

                $builder->where('name', 'LIKE', $like);

                $builder->orWhere(static function(EloquentBuilder $builder) use ($like) {
                    $builder->where('email_public', true);
                    $builder->where('email', 'LIKE', $like);
                })->orWhere(static function(EloquentBuilder $builder) use ($like) {
                    $builder->where('phone_public', true);
                    $builder->where('phone', 'LIKE', $like);
                })->orWhere(static function(EloquentBuilder $builder) use ($like) {
                    $builder->where('website_public', true);
                    $builder->where('website', 'LIKE', $like);
                });

                $builder->orWhereHas('business_type.translations', static function(
                    EloquentBuilder $builder
                ) use ($like) {
                    $builder->where('business_type_translations.name', 'LIKE', $like);
                });

                $builder->orWhereHas('offices', static function(
                    EloquentBuilder $builder
                ) use ($like) {
                    $builder->where(static function(EloquentBuilder $query) use ($like) {
                        $query->where(
                            'address','LIKE', $like
                        );
                    });
                });
            });
        }

        return $query;
    }

    /**
     * @param string|null $key
     * @return EmailFrom
     */
    public static function emailFrom(
        ?string $key = null
    ): EmailFrom {
        if ($activeModel = ($key ? self::findModelByKey($key) : self::activeModel())) {
            return $activeModel->getEmailFrom();
        }

        return EmailFrom::createDefault();
    }

    /**
     * @return EmailFrom
     */
    public function getEmailFrom(): EmailFrom {
        return new EmailFrom(
            $this->email_from_address ?: config('mail.from.address'),
            $this->email_from_name ?: config('mail.from.name')
        );
    }
}
