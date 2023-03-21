<?php

namespace App\Services\EventLogService\Models;

use App\Models\BankConnection;
use App\Models\Employee;
use App\Models\Fund;
use App\Models\Identity;
use App\Models\Implementation;
use App\Models\Voucher;
use App\Models\VoucherTransaction;
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
     * @param array|int $loggable_key
     * @return Builder
     */
    public static function eventsOfTypeQuery(
        string $loggable_class,
        array|int $loggable_key
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
        ], $this->loggable instanceof Voucher ? [
            'sponsor_id' => $attributes['sponsor_id'] ?? $this->loggable->fund?->organization_id,
        ] : []));
    }

    /**
     * @param Employee $consumer
     * @return string|null
     * @noinspection PhpUnused
     */
    public function eventDescriptionLocaleDashboard(Employee $consumer): ?string
    {
        return $this->getEventLocale(self::TRANSLATION_DASHBOARD, $consumer);
    }

    /**
     * @return string|null
     * @noinspection PhpUnused
     */
    public function eventDescriptionLocaleWebshop(): ?string
    {
        return $this->getEventLocale(self::TRANSLATION_WEBSHOP);
    }

    /**
     * @param string $type
     * @param Employee|null $consumer
     * @return string|null
     */
    public function getEventLocale(string $type, ?Employee $consumer = null): ?string
    {
        $attributes = [];
        $eventKey = $this->event;
        $forWebshop = $type == self::TRANSLATION_WEBSHOP;

        if ($this->loggable_type === (new Voucher())->getMorphClass()) {
            $transactionType = Arr::get($this->data, 'voucher_transaction_target') === VoucherTransaction::TARGET_TOP_UP
                ? trans('transaction.type.incoming')
                : trans('transaction.type.outgoing');

            $canSeeAmount = $forWebshop || ($consumer && $this->isSameOrganization($consumer));
            $eventKey .= $eventKey == 'transaction' ? ($canSeeAmount ? ".complete" : '.basic') : '';

            $attributes = array_merge([
                'id' => Arr::get($this->data, 'voucher_id'),
                'amount_locale' => Arr::get($this->data, 'voucher_transaction_amount_locale'),
                'transaction_type' => $transactionType,
            ]);
        }

        if ($this->loggable_type === (new Employee())->getMorphClass()) {
            $attributes = ['email' => Arr::get($this->data, 'employee_email')];
        }

        if ($this->loggable_type === (new Fund())->getMorphClass()) {
            $attributes = array_merge(Arr::only($this->data, [
                'fund_name', 'provider_name', 'product_name', 'sponsor_id', 'fund_id',
            ]), [
                'vouchers_count' => count(Arr::get($this->data, 'fund_export_voucher_ids', [])),
            ]);
        }

        if ($this->loggable_type === (new BankConnection())->getMorphClass()) {
            $attributes = [
                'bank' => Arr::get($this->data, 'bank_connection_account_monetary_account_name'),
                'iban' => Arr::get($this->data, 'bank_connection_account_monetary_account_iban'),
                'state' => Arr::get($this->data, 'bank_connection_state'),
            ];
        }

        $attributes['dashboard_url'] = rtrim(Implementation::active()->urlSponsorDashboard(), '/');

        foreach ($attributes as $key => $attribute) {
            $attributes[$key] = e($attribute);
        }

        return trans("events/$this->loggable_type.$type.$eventKey", $attributes);
    }

    /**
     * @param Employee $consumer
     * @return bool
     */
    public function isSameOrganization(Employee $consumer): bool
    {
        return $consumer
            ->organization
            ->employees_with_trashed->where('identity_address', $this->identity_address)
            ->isNotEmpty();
    }
}
