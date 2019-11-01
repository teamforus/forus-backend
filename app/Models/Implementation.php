<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;

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
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Fund[] $funds
 * @property-read int|null $funds_count
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Implementation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Implementation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Implementation query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Implementation whereCreatedAt($value)
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
 */
class Implementation extends Model
{
    protected $fillable = [
        'id', 'key', 'name', 'url_webshop', 'url_sponsor', 'url_provider',
        'url_validator', 'lon', 'lat'
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
            'id'
        );
    }

    /**
     * @param string $default
     * @return array|string
     */
    public static function activeKey($default = 'general') {
        return request()->header('Client-Key', $default);
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

    public static function general_urls() {
        return [
            'url_webshop'   => config('forus.front_ends.webshop'),
            'url_sponsor'   => config('forus.front_ends.panel-sponsor'),
            'url_provider'  => config('forus.front_ends.panel-provider'),
            'url_validator' => config('forus.front_ends.panel-validator'),
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
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function activeFundsQuery() {
        if (self::activeKey() == 'general') {
            return Fund::query()->has('fund_config')->where('state', 'active');
        }

        return Fund::query()->whereIn('id', function(Builder $query) {
            $query->select('fund_id')->from('fund_configs')->where([
                'implementation_id' => Implementation::query()->where([
                    'key' => self::activeKey()
                ])->first()->id
            ]);
        })->where('state', 'active');
    }

    /**
     * @return Collection
     */
    public static function activeFunds() {
        return self::activeFundsQuery()->get();
    }

    /**
     * @return Collection
     */
    public static function activeProductCategories() {
        if (self::activeKey() == 'general') {
            return ProductCategory::all();
        }

        return ProductCategory::query()->whereIn(
            'id', FundProductCategory::query()->whereIn(
            'fund_id', self::activeFunds()->pluck('id')
        )->pluck('product_category_id')->unique())->get();
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
            ];
        })->flatten()->merge([
            'app-me_app'
        ])->values();
    }
}
