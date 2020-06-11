<?php

namespace App\Models;

use App\Services\DigIdService\Repositories\DigIdRepo;
use App\Services\Forus\Notification\EmailFrom;
use App\Services\MediaService\MediaImageConfig;
use App\Services\MediaService\MediaImagePreset;
use App\Services\MediaService\MediaPreset;
use App\Services\MediaService\MediaService;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Http\Request;

/**
 * App\Models\Implementation
 *
 * @property int $id
 * @property string $key
 * @property string $name
 * @property string $url_webshop
 * @property string $url_sponsor
 * @property string $url_provider
 * @property string $url_validator
 * @property string $url_app
 * @property float|null $lon
 * @property float|null $lat
 * @property bool $digid_enabled
 * @property string $digid_env
 * @property string|null $digid_app_id
 * @property string|null $digid_shared_secret
 * @property string|null $digid_a_select_server
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Fund[] $funds
 * @property-read int|null $funds_count
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Implementation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Implementation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Implementation query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Implementation whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Implementation whereDigidASelectServer($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Implementation whereDigidAppId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Implementation whereDigidEnabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Implementation whereDigidEnv($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Implementation whereDigidSharedSecret($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Implementation whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Implementation whereKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Implementation whereLat($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Implementation whereLon($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Implementation whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Implementation whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Implementation whereUrlApp($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Implementation whereUrlProvider($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Implementation whereUrlSponsor($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Implementation whereUrlValidator($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Implementation whereUrlWebshop($value)
 * @mixin \Eloquent
 * @property string|null $email_from_address
 * @property string|null $email_from_name
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Implementation whereEmailFromAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Implementation whereEmailFromName($value)
 */
class Implementation extends Model
{
    protected $fillable = [
        'id', 'key', 'name', 'url_webshop', 'url_sponsor', 'url_provider',
        'url_validator', 'lon', 'lat', 'email_from_address', 'email_from_name'
    ];

    protected $hidden = [
        'digid_enabled', 'digid_env', 'digid_app_id', 'digid_shared_secret',
        'digid_a_select_server'
    ];

    protected $casts = [
        'digid_enabled' => 'boolean'
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
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function funds() {
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
     * @return \Illuminate\Support\Collection
     */
    public static function active() {
        return self::byKey(self::activeKey());
    }

    /**
     * @param $key
     * @return \Illuminate\Support\Collection
     */
    public static function byKey($key) {
        if ($key == 'general') {
            return collect(self::general_urls());
        }

        return collect(self::query()->where(compact('key'))->first());
    }

    /**
     * @param $key
     * @return Implementation|null
     */
    public static function findModelByKey($key) {
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
    public static function isValidKey($key) {
        return self::implementationKeysAvailable()->search($key) !== false;
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

        if (self::activeKey() == 'general') {
            return Fund::query()->has('fund_config')->whereIn('state', $states);
        }

        return Fund::query()->whereIn('id', function(QueryBuilder $query) {
            $query->select('fund_id')->from('fund_configs')->where([
                'implementation_id' => Implementation::query()->where([
                    'key' => self::activeKey()
                ])->first()->id
            ]);
        })->whereIn('state', $states);
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public static function activeFunds() {
        return self::activeFundsQuery()->get();
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public static function implementationKeysAvailable() {
        return self::query()->pluck('key')->merge([
            'general'
        ]);
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public static function keysAvailable () {
        return self::implementationKeysAvailable()->map(function ($key) {
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
    public function digidEnabled() {
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
    public function getDigid()
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
     * @return mixed|string|null
     */
    public function urlFrontend(string $frontend, string $uri = '') {
        switch ($frontend) {
            case 'webshop': return $this->urlWebshop($uri); break;
            case 'sponsor': return $this->urlSponsorDashboard($uri); break;
            case 'provider': return $this->urlProviderDashboard($uri); break;
            case 'validator': return $this->urlValidatorDashboard($uri); break;
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
     * @return mixed|string
     */
    public function urlValidatorDashboard(string $uri = "/")
    {
        return http_resolve_url($this->url_validator ?? env('PANEL_VALIDATOR_URL'), $uri);
    }

    public function autoValidationEnabled() {
        $oneActiveFund = $this->funds()->where([
                'state' => Fund::STATE_ACTIVE
            ])->count() === 1;

        $oneActiveFundWithAutoValidation = $this->funds()->where([
                'state' => Fund::STATE_ACTIVE,
                'auto_requests_validation' => true
            ])->whereNotNull('default_validator_employee_id')->count() === 1;

        return $oneActiveFund && $oneActiveFundWithAutoValidation;
    }

    public static function platformConfig($value) {
        if (!Implementation::isValidKey(Implementation::activeKey())) {
            return abort(403, 'unknown_implementation_key');
        }

        $ver = request()->input('ver');

        if (preg_match('/[^a-z_\-0-9]/i', $value)) {
            abort(403);
        }

        if (preg_match('/[^a-z_\-0-9]/i', $ver)) {
            abort(403);
        }

        $config = config('forus.features.' . $value . ($ver ? '.' . $ver : ''));

        if (is_array($config)) {
            $config['media'] = collect(MediaService::getMediaConfigs())->map(function(
                MediaImageConfig $mediaConfig
            ) {
                return [
                    'aspect_ratio' => $mediaConfig->getPreviewAspectRatio(),
                    'size' => collect($mediaConfig->getPresets())->map(function(
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

            $implementation = Implementation::active();
            $implementationModel = Implementation::activeModel();

            $config['digid'] = $implementationModel ?
                $implementationModel->digidEnabled() : false;

            $config['auto_validation'] = $implementationModel &&
                $implementationModel->autoValidationEnabled();

            $config['fronts'] = $implementation->only([
                'url_webshop', 'url_sponsor', 'url_provider',
                'url_validator', 'url_app'
            ]);

            $config['map'] = [
                'lon' => doubleval(
                    $implementation['lon'] ?? config('forus.front_ends.map.lon')
                ),
                'lat' => doubleval(
                    $implementation['lat'] ?? config('forus.front_ends.map.lat')
                )
            ];

            $config['implementation_name'] = $implementation->get('name') ?: 'general';
        }

        return $config ?: [];
    }

    public static function searchProviders(Request $request) {
        $query = Organization::query();

        if (Implementation::activeKey() != 'general') {
            $funds = Implementation::activeModel()->funds()->where([
                'state' => Fund::STATE_ACTIVE
            ])->pluck('fund_id');

            $query->whereHas('supplied_funds_approved', function(
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
            $query->whereHas('business_type', function(
                EloquentBuilder $builder
            ) use ($business_type) {
                $builder->where('id', $business_type);
            });
        }

        if ($request->has('fund_id') && (
            $fund_id = $request->input('fund_id'))
        ) {
            $query->whereHas('supplied_funds_approved', function(
                EloquentBuilder $builder
            ) use ($fund_id) {
                $builder->where('funds.id', $fund_id);
            });
        }

        if ($request->has('q') && ($q = $request->input('q'))) {
            $query->where(function(EloquentBuilder $builder) use ($q) {
                $like = '%' . $q . '%';

                $builder->where('name', 'LIKE', $like);

                $builder->orWhere(function(EloquentBuilder $builder) use ($like) {
                    $builder->where('email_public', true);
                    $builder->where('email', 'LIKE', $like);
                })->orWhere(function(EloquentBuilder $builder) use ($like) {
                    $builder->where('phone_public', true);
                    $builder->where('phone', 'LIKE', $like);
                })->orWhere(function(EloquentBuilder $builder) use ($like) {
                    $builder->where('website_public', true);
                    $builder->where('website', 'LIKE', $like);
                });

                $builder->orWhereHas('business_type.translations', function(
                    EloquentBuilder $builder
                ) use ($like) {
                    $builder->where('business_type_translations.name', 'LIKE', $like);
                });

                $builder->orWhereHas('offices', function(
                    EloquentBuilder $builder
                ) use ($like) {
                    $builder->where(function(EloquentBuilder $query) use ($like) {
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
     * @return EmailFrom
     */
    public static function emailFrom(): EmailFrom {
        if ($activeModel = self::activeModel()) {
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
