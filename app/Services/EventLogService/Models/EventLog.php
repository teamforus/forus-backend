<?php

namespace App\Services\EventLogService\Models;

use App\Models\BankConnection;
use App\Models\Employee;
use App\Models\Fund;
use App\Models\Identity;
use App\Models\Implementation;
use App\Models\Voucher;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Arr;

/**
 * App\Services\EventLogService\Models\EventLog
 *
 * @property int $id
 * @property string $loggable_type
 * @property int $loggable_id
 * @property string $event
 * @property string|null $identity_address
 * @property int $original
 * @property array $data
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read string|null $event_locale_dashboard
 * @property-read string|null $event_locale_webshop
 * @property-read string|null $loggable_locale_dashboard
 * @property-read Identity|null $identity
 * @property-read Model|\Eloquent $loggable
 * @method static Builder|EventLog newModelQuery()
 * @method static Builder|EventLog newQuery()
 * @method static Builder|EventLog query()
 * @method static Builder|EventLog whereCreatedAt($value)
 * @method static Builder|EventLog whereData($value)
 * @method static Builder|EventLog whereEvent($value)
 * @method static Builder|EventLog whereId($value)
 * @method static Builder|EventLog whereIdentityAddress($value)
 * @method static Builder|EventLog whereLoggableId($value)
 * @method static Builder|EventLog whereLoggableType($value)
 * @method static Builder|EventLog whereOriginal($value)
 * @method static Builder|EventLog whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class EventLog extends Model
{
    const TRANSLATION_DASHBOARD = 'dashboard';
    const TRANSLATION_WEBSHOP = 'webshop';

    protected $fillable = [
        'event', 'data', 'identity_address', 'original',
    ];

    protected $casts = [
        'data' => 'array',
    ];

    protected $hidden = [
        'data', 'identity_address',
    ];

    /**
     * @return MorphTo
     */
    public function loggable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return BelongsTo
     */
    public function identity(): BelongsTo
    {
        return $this->belongsTo(Identity::class, 'identity_address', 'address');
    }

    /**
     * todo: migrate all eventsOfTypeQuery to eventsOfTypeQuery2
     * @param string $loggable_class
     * @param int|array $loggable_key
     * @return Builder
     */
    public static function eventsOfTypeQuery(
        string $loggable_class,
        $loggable_key
    ): Builder {
        $query = self::query();

        $query->whereHasMorph('loggable', $loggable_class);
        $query->where(static function(Builder $builder) use ($loggable_key) {
            $builder->whereIn('loggable_id', (array) $loggable_key);
        });

        return $query;
    }

    /**
     * @param string $loggableType
     * @param Builder $loggableQuery
     * @return Builder
     */
    public static function eventsOfTypeQuery2(
        string $loggableType,
        Builder $loggableQuery
    ): Builder {
        $query = self::query();

        $query->whereHasMorph('loggable', $loggableType);
        $query->where(static function(Builder $builder) use ($loggableQuery) {
            $builder->whereIn('loggable_id', $loggableQuery->select('id'));
        });

        return $query;
    }

    /**
     * @return string|null
     * @noinspection PhpUnused
     */
    public function getLoggableLocaleDashboardAttribute(): ?string
    {
        $attributes = array_dot($this->data);

        foreach ($attributes as $key => $attribute) {
            $attributes[$key] = e($attribute);
        }

        return trans("events/loggable.$this->loggable_type", array_merge($attributes, [
            'dashboard_url' => rtrim(Implementation::active()->urlSponsorDashboard(), '/'),
        ]));
    }

    /**
     * @return string|null
     * @noinspection PhpUnused
     */
    public function getEventLocaleDashboardAttribute(): ?string
    {
        return $this->getEventLocale(self::TRANSLATION_DASHBOARD);
    }

    /**
     * @return string|null
     * @noinspection PhpUnused
     */
    public function getEventLocaleWebshopAttribute(): ?string
    {
        return $this->getEventLocale(self::TRANSLATION_WEBSHOP);
    }

    /**
     * @param string $type
     * @return string|null
     */
    public function getEventLocale(string $type): ?string
    {
        $attributes = [];

        if ($this->loggable_type === (new Voucher())->getMorphClass()) {
            $attributes = ['id' => Arr::get($this->data, 'voucher_id')];
        }

        if ($this->loggable_type === (new Employee())->getMorphClass()) {
            $attributes = ['email' => Arr::get($this->data, 'employee_email')];
        }

        if ($this->loggable_type === (new Fund())->getMorphClass()) {
            $attributes = array_merge(Arr::only($this->data, [
                'fund_name', 'provider_name', 'product_name', 'sponsor_id', 'fund_id',
            ]), [
                'vouchers_count' => count(Arr::get($this->data, 'fund_export_voucher_ids', [])),
                'dashboard_url' => rtrim(Implementation::active()->urlSponsorDashboard(), '/'),
            ]);
        }

        if ($this->loggable_type === (new BankConnection())->getMorphClass()) {
            $attributes = [
                'bank' => Arr::get($this->data, 'bank_connection_account_monetary_account_name'),
                'iban' => Arr::get($this->data, 'bank_connection_account_monetary_account_iban'),
                'state' => Arr::get($this->data, 'bank_connection_state'),
            ];
        }

        foreach ($attributes as $key => $attribute) {
            $attributes[$key] = e($attribute);
        }

        return trans("events/$this->loggable_type.$type.$this->event", $attributes);
    }
}
