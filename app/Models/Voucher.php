<?php

namespace App\Models;

use App\Events\ProductReservations\ProductReservationCreated;
use App\Events\Vouchers\ProductVoucherShared;
use App\Events\Vouchers\VoucherAssigned;
use App\Events\Vouchers\VoucherCreated;
use App\Events\Vouchers\VoucherDeactivated;
use App\Models\Data\VoucherExportData;
use App\Models\Traits\HasFormattedTimestamps;
use App\Scopes\Builders\VoucherQuery;
use App\Services\EventLogService\Models\EventLog;
use App\Services\EventLogService\Traits\HasLogs;
use App\Services\Forus\Identity\Models\Identity;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * App\Models\Voucher
 *
 * @property int $id
 * @property int $fund_id
 * @property string|null $identity_address
 * @property string $state
 * @property string $amount
 * @property int $limit_multiplier
 * @property bool $returnable
 * @property int|null $product_reservation_id
 * @property string|null $note
 * @property int|null $employee_id
 * @property string|null $activation_code
 * @property string|null $activation_code_uid
 * @property int|null $fund_backoffice_log_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int|null $product_id
 * @property int|null $parent_id
 * @property \Illuminate\Support\Carbon|null $expire_at
 * @property-read \App\Models\Employee|null $employee
 * @property-read \App\Models\Fund $fund
 * @property-read \App\Models\FundBackofficeLog|null $fund_backoffice_log
 * @property-read bool $activated
 * @property-read float $amount_available
 * @property-read float $amount_available_cached
 * @property-read string|null $created_at_string
 * @property-read string|null $created_at_string_locale
 * @property-read bool $deactivated
 * @property-read bool $expired
 * @property-read bool $has_product_vouchers
 * @property-read bool $has_transactions
 * @property-read bool $in_use
 * @property-read bool $is_granted
 * @property-read \Carbon\Carbon|\Illuminate\Support\Carbon $last_active_day
 * @property-read string $state_locale
 * @property-read string $type
 * @property-read string|null $updated_at_string
 * @property-read string|null $updated_at_string_locale
 * @property-read bool $used
 * @property-read Identity|null $identity
 * @property-read EventLog|null $last_deactivation_log
 * @property-read \App\Models\VoucherTransaction|null $last_transaction
 * @property-read Collection|EventLog[] $logs
 * @property-read int|null $logs_count
 * @property-read Voucher|null $parent
 * @property-read Collection|\App\Models\PhysicalCardRequest[] $physical_card_requests
 * @property-read int|null $physical_card_requests_count
 * @property-read Collection|\App\Models\PhysicalCard[] $physical_cards
 * @property-read int|null $physical_cards_count
 * @property-read \App\Models\Product|null $product
 * @property-read \App\Models\ProductReservation|null $product_reservation
 * @property-read Collection|\App\Models\ProductReservation[] $product_reservations
 * @property-read int|null $product_reservations_count
 * @property-read Collection|Voucher[] $product_vouchers
 * @property-read int|null $product_vouchers_count
 * @property-read \App\Models\VoucherToken|null $token_with_confirmation
 * @property-read \App\Models\VoucherToken|null $token_without_confirmation
 * @property-read Collection|\App\Models\VoucherToken[] $tokens
 * @property-read int|null $tokens_count
 * @property-read Collection|\App\Models\VoucherTransaction[] $transactions
 * @property-read int|null $transactions_count
 * @property-read \App\Models\VoucherRelation|null $voucher_relation
 * @method static Builder|Voucher newModelQuery()
 * @method static Builder|Voucher newQuery()
 * @method static Builder|Voucher query()
 * @method static Builder|Voucher whereActivationCode($value)
 * @method static Builder|Voucher whereActivationCodeUid($value)
 * @method static Builder|Voucher whereAmount($value)
 * @method static Builder|Voucher whereCreatedAt($value)
 * @method static Builder|Voucher whereEmployeeId($value)
 * @method static Builder|Voucher whereExpireAt($value)
 * @method static Builder|Voucher whereFundBackofficeLogId($value)
 * @method static Builder|Voucher whereFundId($value)
 * @method static Builder|Voucher whereId($value)
 * @method static Builder|Voucher whereIdentityAddress($value)
 * @method static Builder|Voucher whereLimitMultiplier($value)
 * @method static Builder|Voucher whereNote($value)
 * @method static Builder|Voucher whereParentId($value)
 * @method static Builder|Voucher whereProductId($value)
 * @method static Builder|Voucher whereProductReservationId($value)
 * @method static Builder|Voucher whereReturnable($value)
 * @method static Builder|Voucher whereState($value)
 * @method static Builder|Voucher whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Voucher extends Model
{
    use HasLogs, HasFormattedTimestamps;

    public const EVENT_CREATED_BUDGET = 'created_budget';
    public const EVENT_CREATED_PRODUCT = 'created_product';
    public const EVENT_SHARED = 'shared';
    public const EVENT_EXPIRED_BUDGET = 'expired';
    public const EVENT_EXPIRED_PRODUCT = 'expired';
    public const EVENT_EXPIRING_SOON_BUDGET = 'expiring_soon_budget';
    public const EVENT_EXPIRING_SOON_PRODUCT = 'expiring_soon_product';
    public const EVENT_ASSIGNED = 'assigned';
    public const EVENT_ACTIVATED = 'activated';
    public const EVENT_DEACTIVATED = 'deactivated';

    public const EVENT_TRANSACTION = 'transaction';
    public const EVENT_TRANSACTION_PRODUCT = 'transaction_product';
    public const EVENT_TRANSACTION_SUBSIDY = 'transaction_subsidy';

    public const TYPE_BUDGET = 'regular';
    public const TYPE_PRODUCT = 'product';

    public const STATE_ACTIVE = 'active';
    public const STATE_PENDING = 'pending';
    public const STATE_DEACTIVATED = 'deactivated';

    public const EVENTS_TRANSACTION = [
        self::EVENT_TRANSACTION,
        self::EVENT_TRANSACTION_PRODUCT,
        self::EVENT_TRANSACTION_SUBSIDY,
    ];

    public const EVENTS_CREATED = [
        self::EVENT_CREATED_BUDGET,
        self::EVENT_CREATED_PRODUCT,
    ];

    public const TYPES = [
        self::TYPE_BUDGET,
        self::TYPE_PRODUCT,
    ];

    public const STATES = [
        self::STATE_ACTIVE,
        self::STATE_PENDING,
        self::STATE_DEACTIVATED,
    ];

    protected $withCount = [
        'transactions'
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'fund_id', 'identity_address', 'limit_multiplier', 'amount', 'product_id',
        'parent_id', 'expire_at', 'note', 'employee_id', 'returnable', 'state',
        'activation_code', 'activation_code_uid', 'fund_backoffice_log_id',
        'product_reservation_id',
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'expire_at',
    ];

    protected $casts = [
        'returnable' => 'boolean',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     * @noinspection PhpUnused
     */
    public function fund(): BelongsTo
    {
        return $this->belongsTo(Fund::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     * @noinspection PhpUnused
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * @return MorphOne
     * @noinspection PhpUnused
     */
    public function last_deactivation_log(): MorphOne
    {
        return $this->morphOne(EventLog::class, 'loggable')->where([
            'event' => self::EVENT_DEACTIVATED,
        ]);
    }

    /**
     * @return BelongsTo
     */
    public function product_reservation(): BelongsTo
    {
        return $this->belongsTo(ProductReservation::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     * @noinspection PhpUnused
     */
    public function fund_backoffice_log(): BelongsTo
    {
        return $this->belongsTo(FundBackofficeLog::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function identity(): BelongsTo
    {
        return $this->belongsTo(Identity::class, 'identity_address', 'address');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     * @noinspection PhpUnused
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     * @noinspection PhpUnused
     */
    public function physical_cards(): HasMany
    {
        return $this->hasMany(PhysicalCard::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function product_reservations(): HasMany
    {
        return $this->hasMany(ProductReservation::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     * @noinspection PhpUnused
     */
    public function voucher_relation(): HasOne
    {
        return $this->hasOne(VoucherRelation::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     * @noinspection PhpUnused
     */
    public function physical_card_requests(): HasMany
    {
        return $this->hasMany(PhysicalCardRequest::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     * @noinspection PhpUnused
     */
    public function product(): BelongsTo
    {
        return query_with_trashed($this->belongsTo(
            Product::class, 'product_id', 'id'
        ));
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     * @noinspection PhpUnused
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(VoucherTransaction::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     * @noinspection PhpUnused
     */
    public function last_transaction(): HasOne {
        return $this->hasOne(VoucherTransaction::class)->orderByDesc('created_at');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function product_vouchers(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->where(function(Builder $builder) {
            $builder->whereDoesntHave('product_reservation');
            $builder->orWhereHas('product_reservation', function (Builder $builder) {
                $builder->whereIn('state', [
                    ProductReservation::STATE_PENDING,
                    ProductReservation::STATE_ACCEPTED
                ]);
            });
        });
    }

    /**
     * @return string
     * @noinspection PhpUnused
     */
    public function getTypeAttribute(): string
    {
        return $this->product_id ? 'product' : 'regular';
    }

    /**
     * @return float
     * @noinspection PhpUnused
     */
    public function getAmountAvailableAttribute(): float
    {
        return round($this->amount -
            $this->transactions()->sum('amount') -
            $this->product_vouchers()->sum('amount'), 2);
    }

    /**
     * @return float
     * @noinspection PhpUnused
     */
    public function getAmountAvailableCachedAttribute(): float
    {
        return round($this->amount -
            $this->transactions->sum('amount') -
            $this->product_vouchers->sum('amount'), 2);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     * @noinspection PhpUnused
     */
    public function tokens(): HasMany
    {
        return $this->hasMany(VoucherToken::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     * @noinspection PhpUnused
     */
    public function token_without_confirmation(): HasOne
    {
        return $this->hasOne(VoucherToken::class)->where([
            'need_confirmation' => false
        ]);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     * @noinspection PhpUnused
     */
    public function token_with_confirmation(): HasOne
    {
        return $this->hasOne(VoucherToken::class)->where([
            'need_confirmation' => true
        ]);
    }

    /**
     * The voucher is expired
     *
     * @return bool
     * @noinspection PhpUnused
     */
    public function getExpiredAttribute(): bool
    {
        return $this->fund->end_date->endOfDay()->isPast() || $this->expire_at->endOfDay()->isPast();
    }

    /**
     * The voucher is deactivated
     *
     * @return bool
     * @noinspection PhpUnused
     */
    public function getDeactivatedAttribute(): bool
    {
        return $this->isDeactivated();
    }

    /**
     * The voucher is activated
     *
     * @return bool
     * @noinspection PhpUnused
     */
    public function getActivatedAttribute(): bool
    {
        return $this->isActivated();
    }

    /**
     * @return string
     */
    public function getStateLocaleAttribute(): string
    {
        return trans('states/vouchers.' . $this->state);
    }

    /**
     * The voucher is expired
     *
     * @return bool
     * @noinspection PhpUnused
     */
    public function getUsedAttribute(): bool
    {
        return $this->type === 'product' ? $this->transactions->count() > 0 :
            $this->amount_available_cached === 0.0;
    }

    /**
     * @return Carbon|\Illuminate\Support\Carbon
     * @noinspection PhpUnused
     */
    public function getLastActiveDayAttribute()
    {
        return $this->expire_at;
    }

    /**
     * @param string|null $email
     */
    public function sendToEmail(string $email = null): void
    {
        /** @var VoucherToken $voucherToken */
        $voucherToken = $this->tokens()->where([
            'need_confirmation' => false
        ])->first();

        if ($voucherToken->voucher->type === 'product') {
            $fund_product_name = $voucherToken->voucher->product->name;
        } else {
            $fund_product_name = $voucherToken->voucher->fund->name;
        }

        resolve('forus.services.notification')->sendVoucher(
            $email,
            $voucherToken->voucher->fund->fund_config->implementation->getEmailFrom(),
            $fund_product_name,
            $this->amount,
            format_date_locale($this->expire_at, 'long_date_locale'),
            $fund_product_name,
            $voucherToken->address
        );
    }

    /**
     * @param string|null $email
     */
    public function assignedVoucherEmail(string $email = null): void
    {
        $mailFrom = $this->fund->fund_config->implementation->getEmailFrom();
        $expireDate = format_date_locale($this->expire_at, 'long_date_locale');

        if ($this->isBudgetType()) {
            $type = $this->fund->isTypeBudget() ? 'budget' : 'subsidies';
        } else {
            $type = 'product';
        }

        resolve('forus.services.notification')->assignVoucher($email, $mailFrom, $type, [
            'fund_name' => $this->fund->name,
            'link_webshop' => $this->fund->urlWebshop(),
            'qr_token' => $this->token_without_confirmation->address,
            'product_name' => $this->product->name ?? null,
            'provider_name' => $this->product->organization->name ?? null,
            'provider_email' => $this->product->organization->email ?? null,
            'provider_phone' => $this->product->organization->phone ?? null,
            'provider_website' => $this->product->organization->website ?? null,
            'sponsor_name' => $this->fund->organization->name ?? null,
            'sponsor_email' => $this->fund->organization->email ?? null,
            'sponsor_phone' => $this->fund->organization->phone ?? null,
            'sponsor_description' => $this->fund->organization->description ?? null,
            'voucher_amount' => $this->amount,
            'voucher_last_active_day' => $expireDate,
        ]);
    }

    /**
     * @param string $message
     * @param bool $sendCopyToUser
     */
    public function shareVoucherEmail(string $message, bool $sendCopyToUser = false): void
    {
        /** @var VoucherToken $voucherToken */
        $voucherToken = $this->tokens()->where([
            'need_confirmation' => false
        ])->first();

        $voucher = $voucherToken->voucher;

        if ($voucher->type === 'product') {
            ProductVoucherShared::dispatch($voucher, $message, $sendCopyToUser);
        }
    }

    /**
     * @param int $days
     */
    public static function checkVoucherExpireQueue(int $days = 4 * 7): void
    {
        $date = now()->addDays($days)->startOfDay()->format('Y-m-d');

        $vouchers = self::query()
            ->whereNull('product_id')
            ->whereNotNull('identity_address')
            ->with(['fund', 'fund.organization'])
            ->whereDate('expire_at', '=', $date)
            ->get();

        /** @var self $voucher */
        foreach ($vouchers as $voucher) {
            if ($voucher->amount_available_cached > 0) {
                $voucher->sendVoucherExpireMail();
            }
        }
    }

    /**
     * Send voucher expiration warning email to requester identity
     */
    public function sendVoucherExpireMail(): void {
        $voucher = $this;
        $notificationService = resolve('forus.services.notification');
        $recordRepo = resolve('forus.services.record');
        $primaryEmail = $recordRepo->primaryEmailByAddress($voucher->identity_address);

        $fund_name = $voucher->fund->name;
        $sponsor_name = $voucher->fund->organization->name;
        $start_date = $voucher->fund->start_date;
        $end_date = $voucher->fund->end_date;
        $phone = $voucher->fund->organization->phone;
        $email = $voucher->fund->organization->email;
        $webshopLink = $voucher->fund->urlWebshop();

        $notificationService->voucherExpireSoon(
            $primaryEmail,
            $voucher->fund->fund_config->implementation->getEmailFrom(),
            $fund_name,
            $sponsor_name,
            $start_date,
            $end_date,
            $phone,
            $email,
            $webshopLink
        );
    }

    /**
     * @param Request $request
     * @return Builder
     */
    public static function search(
        Request $request
    ): Builder {
        /** @var Builder $query */
        $query = self::query();
        $granted = $request->input('granted');

        if ($granted) {
            $query->whereNotNull('identity_address');
        } elseif ($granted !== null) {
            $query->whereNull('identity_address');
        }

        if ($request->has('amount_min')) {
            $query->where('amount', '>=', $request->input('amount_min'));
        }

        if ($request->has('amount_max')) {
            $query->where('amount', '<=', $request->input('amount_max'));
        }

        if ($request->has('from')) {
            $query->where('created_at', '>=', Carbon::parse(
                $request->input('from'))->startOfDay()
            );
        }

        if ($request->has('to')) {
            $query->where('created_at', '<=', Carbon::parse(
                $request->input('to'))->endOfDay()
            );
        }

        return $query;
    }

    /**
     * @param Request $request
     * @param Organization $organization
     * @param Fund|null $fund
     * @return Builder
     */
    public static function searchSponsorQuery(
        Request $request,
        Organization $organization,
        Fund $fund = null
    ): Builder {
        $query = VoucherQuery::whereVisibleToSponsor(self::search($request));
        $unassignedOnly = $request->input('unassigned');
        $in_use = $request->input('in_use');
        $expired = $request->input('expired');

        $query->whereHas('fund', static function(Builder $query) use ($organization, $fund) {
            $query->where('organization_id', $organization->id);

            if ($fund) {
                $query->where('id', $fund->id);
            }
        });

        if ($state = $request->input('state')) {
            $query->where('state', $state);
        }

        if ($unassignedOnly) {
            $query->whereNull('identity_address');
        } else if ($unassignedOnly !== null) {
            $query->whereNotNull('identity_address');
        }

        switch ($request->input('type')) {
            case 'fund_voucher': $query->whereNull('product_id'); break;
            case 'product_voucher': $query->whereNotNull('product_id'); break;
        }

        switch ($request->input('source', 'employee')) {
            case 'all': break;
            case 'user': $query->whereNull('employee_id'); break;
            case 'employee': $query->whereNotNull('employee_id'); break;
            default: abort(403);
        }

        if ($request->has('email') && $email = $request->input('email')) {
            $query->where('identity_address', identity_repo()->getAddress($email) ?: '_');
        }

        if ($request->has('bsn') && $bsn = $request->input('bsn')) {
            $query->where(static function(Builder $builder) use ($bsn) {
                $builder->where('identity_address', record_repo()->identityAddressByBsn($bsn) ?: '-');
                $builder->orWhereHas('voucher_relation', function (Builder $builder) use ($bsn) {
                    $builder->where(compact('bsn'));
                });
            });
        }

        if ($expired !== null) {
            $query = $expired ? VoucherQuery::whereExpired($query) : VoucherQuery::whereNotExpired($query);
        }

        if ($q = $request->input('q')) {
            $query = VoucherQuery::whereSearchSponsorQuery($query, $q);
        }

        if ($request->has('in_use')) {
            $in_use ? $query->whereHas('transactions') : $query->whereDoesntHave('transactions');
        }

        return $query->orderBy(
            $request->input('sort_by', 'created_at'),
            $request->input('sort_order', 'asc')
        );
    }

    /**
     * @param Request $request
     * @param Organization $organization
     * @param Fund|null $fund
     * @return Builder[]|Collection
     */
    public static function searchSponsor(
        Request $request,
        Organization $organization,
        Fund $fund = null
    ) {
        return self::searchSponsorQuery($request, $organization, $fund)->get();
    }

    /**
     * @return bool
     * @noinspection PhpUnused
     */
    public function getIsGrantedAttribute(): bool
    {
        return !empty($this->identity_address);
    }

    /**
     * @return bool
     * @noinspection PhpUnused
     */
    public function getHasTransactionsAttribute(): bool
    {
        return count($this->transactions) > 0;
    }

    /**
     * @return bool
     * @noinspection PhpUnused
     */
    public function getHasProductVouchersAttribute(): bool
    {
        return $this->product_vouchers->count() > 0;
    }

    /**
     * @return bool
     * @noinspection PhpUnused
     */
    public function getInUseAttribute(): bool
    {
        return $this->has_transactions || $this->has_product_vouchers;
    }

    /**
     * Assign voucher to identity
     *
     * @param string $identity_address
     * @return $this
     */
    public function assignToIdentity(string $identity_address): self
    {
        $this->update([
            'identity_address' => $identity_address,
            'state' => self::STATE_ACTIVE,
        ]);

        VoucherAssigned::dispatch($this);

        return $this;
    }

    /**
     * @param Product $product
     * @param ProductReservation|null $productReservation
     * @return Voucher|\Illuminate\Database\Eloquent\Model
     */
    public function buyProductVoucher(
        Product $product,
        ProductReservation $productReservation = null
    ): Voucher {
        $voucherExpireAt = array_first(array_sort(array_filter([
            $product->expire_at,
            $this->expire_at,
            $this->fund->end_date
        ])));

        $voucher = self::create([
            'product_reservation_id'    => $productReservation ? $productReservation->id : null,
            'identity_address'          => $this->identity_address,
            'parent_id'                 => $this->id,
            'fund_id'                   => $this->fund_id,
            'product_id'                => $product->id,
            'amount'                    => $product->price,
            'returnable'                => false,
            'expire_at'                 => $voucherExpireAt
        ]);

        VoucherCreated::dispatch($voucher, !$productReservation, !$productReservation);

        return $voucher;
    }

    /**
     * @return bool
     */
    public function isActivated(): bool
    {
        return $this->state === self::STATE_ACTIVE;
    }

    /**
     * @return bool
     */
    public function isPending(): bool
    {
        return $this->state === self::STATE_PENDING;
    }

    /**
     * @return bool
     */
    public function isDeactivated(): bool
    {
        return $this->state === self::STATE_DEACTIVATED;
    }

    /**
     * @param Product $product
     * @param Employee|null $employee
     * @param string|null $note
     * @return ProductReservation
     * @throws \Exception
     */
    public function reserveProduct(
        Product $product,
        ?Employee $employee = null,
        string $note = null
    ): ProductReservation {
        $isSubsidy = $this->fund->isTypeSubsidy();
        $fundProviderProduct = $isSubsidy ? $product->getSubsidyDetailsForFund($this->fund) : null;
        $amount = $isSubsidy && $fundProviderProduct ? $fundProviderProduct->amount : $product->price;

        /** @var ProductReservation $reservation */
        $reservation = $this->product_reservations()->create(array_merge([
            'code'                      => ProductReservation::makeCode(),
            'note'                      => $note,
            'amount'                    => $amount,
            'state'                     => ProductReservation::STATE_PENDING,
            'product_id'                => $product->id,
            'employee_id'               => $employee ? $employee->id : null,
            'fund_provider_product_id'  => $fundProviderProduct ? $fundProviderProduct->id : null,
            'expire_at'                 => $this->calcExpireDateForProduct($product)
        ], $product->only('price', 'price_type', 'price_discount')));

        $reservation->makeVoucher();

        ProductReservationCreated::dispatch($reservation);

        return $reservation;
    }

    /**
     * Calculate expiration date for product when bought using this voucher.
     * @param Product $product
     * @return Carbon|null
     */
    public function calcExpireDateForProduct(Product $product): ?Carbon
    {
        return array_first(array_sort(array_filter([
            $product->expire_at,
            $this->expire_at,
            $this->fund->end_date
        ])));
    }

    /**
     * @param Collection $vouchers
     * @param $exportType
     * @return string
     */
    public static function zipVouchers(Collection $vouchers, $exportType): string {
        $vouchersData = [];
        $vouchersDataNames = [];
        $token_generator = resolve('token_generator');
        $zipPath = storage_path('vouchers-export');

        do {
            $zipFile = sprintf('%s/%s.zip', $zipPath, $token_generator->generate(64));
        } while (file_exists($zipFile));

        if (!file_exists($zipPath) && !mkdir($zipPath, 0777, true) && !is_dir($zipPath)) {
            throw new RuntimeException(sprintf('Directory "%s" was not created', $zipPath));
        }

        $fp = fopen('php://temp/maxmemory:1048576', 'wb');
        $zip = new \ZipArchive();
        $zip->open($zipFile, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

        if ($exportType === 'png') {
            $zip->addEmptyDir('images');
        }

        if ($vouchers->count() > 0) {
            fputcsv($fp, array_keys((new VoucherExportData($vouchers[0]))->toArray()));
        }

        foreach ($vouchers as $voucher) {
            do {
                $voucherData = new VoucherExportData($voucher);
            } while(in_array($voucherData->getName(), $vouchersDataNames, true));

            fputcsv($fp, $voucherData->toArray());
            $vouchersData[] = $voucherData;
            $vouchersDataNames[] = $voucherData->getName();

            if ($exportType === 'png') {
                $zip->addFromString(
                    sprintf("images/%s.png", $voucherData->getName()),
                    make_qr_code('voucher', $voucher->token_without_confirmation->address)
                );
            }
        }

        if ($exportType === 'pdf') {
            $pdf = resolve('dompdf.wrapper');
            $pdf->loadView('pdf.vouchers_export', compact('vouchersData'));
            $zip->addFromString('qr_codes.pdf', $pdf->output());
        }

        rewind($fp);
        $zip->addFromString('qr_codes.csv', stream_get_contents($fp));
        fclose($fp);

        $zip->close();

        return $zipFile;
    }

    /**
     * @param Collection $vouchers
     * @param string $exportType
     * @param bool|null $data_only
     * @return array
     */
    public static function zipVouchersData(
        Collection $vouchers,
        string $exportType,
        ?bool $data_only = true
    ): array {
        $vouchersData = [];
        $vouchersDataNames = [];
        $vouchers->load(
            'transactions', 'voucher_relation', 'product', 'fund',
            'token_without_confirmation', 'identity.primary_email'
        );

        $fp = fopen('php://temp/maxmemory:1048576', 'wb');

        if ($vouchers->count() > 0) {
            fputcsv($fp, array_keys((new VoucherExportData($vouchers[0], $data_only))->toArray()));
        }

        foreach ($vouchers as $voucher) {
            /** @var Voucher $voucher */
            do {
                $voucherData = new VoucherExportData($voucher, $data_only);
            } while(!$data_only && in_array($voucherData->getName(), $vouchersDataNames, true));

            fputcsv($fp, $voucherData->toArray());
            $vouchersDataNames[] = $voucherData->getName();

            if ($exportType === 'png' && !$data_only) {
                $vouchersData[] = [
                    'name' => $voucherData->getName(),
                    'value' => $voucher->token_without_confirmation->address
                ];
            }
        }

        rewind($fp);
        $rawCsv = stream_get_contents($fp);
        fclose($fp);

        return compact('rawCsv', 'vouchersData');
    }

    /**
     * @return bool
     */
    public function isBudgetType(): bool
    {
        return $this->type === self::TYPE_BUDGET;
    }

    /**
     * @return bool
     */
    public function isProductType(): bool
    {
        return $this->type === self::TYPE_PRODUCT;
    }

    /**
     * @param string $address
     * @param string|null $identity_address
     * @return Voucher|null
     */
    public static function findByAddress(
        string $address,
        ?string $identity_address = null
    ): ?Voucher {
        return self::whereHas('tokens', static function(Builder $builder) use ($address) {
            $builder->where('address', '=', $address);
        })->where(static function(Builder $builder) use ($identity_address) {
            $identity_address && $builder->where('identity_address', '=', $identity_address);
        })->firstOrFail();
    }

    /**
     * @param string $number
     * @param string|null $identity_address
     * @return Voucher|null
     */
    public static function findByPhysicalCard(
        string $number,
        ?string $identity_address = null
    ): ?Voucher {
        return self::whereHas('fund.fund_config', static function (Builder $builder) {
            $builder->where('allow_physical_cards', '=', true);
        })->whereHas('physical_cards', static function (Builder $builder) use ($number) {
            $builder->where('code', '=', $number);
        })->where(static function(Builder $builder) use ($identity_address) {
            $identity_address && $builder->where('identity_address', '=', $identity_address);
        })->first();
    }

    /**
     * @param $value
     * @return Voucher|null
     */
    public static function findByAddressOrPhysicalCard($value): ?Voucher
    {
        return self::findByPhysicalCard($value) ?: self::findByAddress($value);
    }

    /**
     * Set voucher relation to bsn number.
     *
     * @param string $bsn
     * @return VoucherRelation|\Illuminate\Database\Eloquent\Model
     */
    public function setBsnRelation(string $bsn): VoucherRelation
    {
        $this->voucher_relation()->delete();

        /** @var VoucherRelation $voucher_relation */
        return $this->voucher_relation()->create(compact('bsn'));
    }

    /**
     * @param string $identity_address
     */
    public static function assignAvailableToIdentityByBsn(
        string $identity_address
    ): void {
        if (!$bsn = record_repo()->bsnByAddress($identity_address)) {
            return;
        }

        /** @var Builder $query */
        $query = self::whereNull('identity_address');
        $query->whereHas('voucher_relation', static function(Builder $builder) use ($bsn) {
            $builder->where('bsn', '=', $bsn);
        })->get()->each(static function(Voucher $voucher) {
            $voucher->voucher_relation->assignIfExists();
        });
    }

    /**
     * @param string|null $activation_code_uid
     * @return $this
     */
    public function makeActivationCode(string $activation_code_uid = null): self
    {
        $queryUnused = self::whereHas('fund', function(Builder $builder) {
            $builder->where('organization_id', $this->fund->organization_id);
        })->whereNull('identity_address')->where(compact('activation_code_uid'));

        $queryUsed = self::whereHas('fund', function(Builder $builder) {
            $builder->where('organization_id', $this->fund->organization_id);
        })->whereNotNull('identity_address')->where(compact('activation_code_uid'));

        if (!is_null($activation_code_uid) && $queryUnused->exists()) {
            /** @var Voucher $voucher */
            $voucher = $queryUnused->first();
            $activation_code = $voucher->activation_code;
        } else {
            $activation_code = token_generator_callback(static function($value) {
                return Prevalidation::whereUid($value)->doesntExist() &&
                    Voucher::whereActivationCode($value)->doesntExist();
            }, 4, 2);
        }

        if (!is_null($activation_code_uid) && $oldVoucher = $queryUsed->first()) {
            $this->assignToIdentity($oldVoucher->identity_address);
        }

        return $this->updateModel([
            'activation_code' => $activation_code,
            'activation_code_uid' => $activation_code_uid,
        ]);
    }

    /**
     * @param string $code
     * @return PhysicalCard|Model|mixed
     */
    public function addPhysicalCard(string $code): PhysicalCard
    {
        return $this->physical_cards()->create(compact('code'));
    }

    /**
    // TODO: cleanup
     * @return bool
     */
    public function needsTransactionReview(): bool
    {
        if ($threshold = env('VOUCHER_TRANSACTION_REVIEW_THRESHOLD', 5)) {
            return $this->transactions()->where(
                'created_at', '>=', now()->subSeconds($threshold)
            )->exists();
        }

        return false;
    }

    /**
     * @param string $note
     * @param Employee $employee
     * @return Voucher
     */
    public function activateAsSponsor(
        string $note,
        Employee $employee
    ): Voucher {
        $this->update([
            'state' => self::STATE_ACTIVE,
        ]);

        $this->log(self::EVENT_ACTIVATED, [
            'voucher' => $this,
            'employee' => $employee,
        ], compact('note'));

        return $this;
    }

    /**
     * @param string $note
     * @param bool $notifyByEmail
     * @param Employee|null $employee
     * @return $this
     */
    public function deactivate(
        string $note = '',
        bool $notifyByEmail = false,
        ?Employee $employee = null
    ): Voucher {
        $this->update([
            'state' => self::STATE_DEACTIVATED,
        ]);

        VoucherDeactivated::dispatch($this, $note, $employee, $notifyByEmail);

        return $this;
    }

    /**
     * @return Collection
     */
    public function sponsorHistoryLogs(): Collection
    {
        return $this->logs->whereIn('event', array_merge([
            self::EVENT_EXPIRED_BUDGET,
            self::EVENT_EXPIRED_PRODUCT,
            self::EVENT_DEACTIVATED,
            self::EVENT_ACTIVATED,
            self::EVENT_ASSIGNED,
        ], self::EVENTS_CREATED, self::EVENTS_TRANSACTION))->merge(
            EventLog::query()->where([
                'loggable_type' => 'physical_card_request'
            ])->whereIn('loggable_id', $this->physical_card_requests->pluck('id')->toArray())
        );
    }

    /**
     * @return Collection
     */
    public function requesterHistoryLogs(): Collection
    {
        return $this->logs->whereIn('event', array_merge([
            self::EVENT_EXPIRED_BUDGET,
            self::EVENT_EXPIRED_PRODUCT,
            self::EVENT_DEACTIVATED,
            self::EVENT_ACTIVATED,
        ], self::EVENTS_CREATED));
    }

    /**
     * @return Carbon|null
     */
    public function deactivationDate(): ?Carbon
    {
        return $this->last_deactivation_log ? $this->last_deactivation_log->created_at : null;
    }
}
