<?php

namespace App\Models;

use App\Events\ProductReservations\ProductReservationCreated;
use App\Events\Reimbursements\ReimbursementCreated;
use App\Events\Reimbursements\ReimbursementSubmitted;
use App\Events\Vouchers\ProductVoucherShared;
use App\Events\Vouchers\VoucherAssigned;
use App\Events\Vouchers\VoucherCreated;
use App\Events\Vouchers\VoucherDeactivated;
use App\Events\Vouchers\VoucherPhysicalCardRequestedEvent;
use App\Events\Vouchers\VoucherSendToEmailEvent;
use App\Events\VoucherTransactions\VoucherTransactionCreated;
use App\Exports\VoucherExport;
use App\Http\Requests\BaseFormRequest;
use App\Models\Data\VoucherExportData;
use App\Models\Traits\HasDbTokens;
use App\Models\Traits\HasFormattedTimestamps;
use App\Scopes\Builders\VoucherQuery;
use App\Scopes\Builders\VoucherSubQuery;
use App\Services\BackofficeApiService\BackofficeApi;
use App\Services\EventLogService\Models\EventLog;
use App\Services\EventLogService\Traits\HasLogs;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Excel as ExcelModel;
use Illuminate\Support\Carbon;
use ZipArchive;

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
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property int|null $product_id
 * @property int|null $parent_id
 * @property Carbon|null $expire_at
 * @property-read Collection|\App\Models\VoucherTransaction[] $all_transactions
 * @property-read int|null $all_transactions_count
 * @property-read \App\Models\FundBackofficeLog|null $backoffice_log_eligible
 * @property-read \App\Models\FundBackofficeLog|null $backoffice_log_first_use
 * @property-read \App\Models\FundBackofficeLog|null $backoffice_log_received
 * @property-read Collection|\App\Models\FundBackofficeLog[] $backoffice_logs
 * @property-read int|null $backoffice_logs_count
 * @property-read \App\Models\Employee|null $employee
 * @property-read \App\Models\Fund $fund
 * @property-read bool $activated
 * @property-read string $amount_available
 * @property-read string $amount_available_cached
 * @property-read string $amount_spent
 * @property-read string $amount_spent_cached
 * @property-read string $amount_top_up
 * @property-read string $amount_top_up_cached
 * @property-read string $amount_total
 * @property-read string $amount_total_cached
 * @property-read string|null $created_at_string
 * @property-read string|null $created_at_string_locale
 * @property-read bool $deactivated
 * @property-read bool $expired
 * @property-read \Illuminate\Support\Carbon|null $first_use_date
 * @property-read bool $has_reservations
 * @property-read bool $has_transactions
 * @property-read bool $in_use
 * @property-read bool $is_external
 * @property-read bool $is_granted
 * @property-read \Illuminate\Support\Carbon|null $last_active_day
 * @property-read string $state_locale
 * @property-read string $type
 * @property-read string|null $updated_at_string
 * @property-read string|null $updated_at_string_locale
 * @property-read bool $used
 * @property-read \App\Models\Identity|null $identity
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
 * @property-read Collection|\App\Models\Reimbursement[] $reimbursements
 * @property-read int|null $reimbursements_count
 * @property-read Collection|\App\Models\Reimbursement[] $reimbursements_pending
 * @property-read int|null $reimbursements_pending_count
 * @property-read \App\Models\VoucherToken|null $token_with_confirmation
 * @property-read \App\Models\VoucherToken|null $token_without_confirmation
 * @property-read Collection|\App\Models\VoucherToken[] $tokens
 * @property-read int|null $tokens_count
 * @property-read Collection|\App\Models\VoucherTransaction[] $top_up_transactions
 * @property-read int|null $top_up_transactions_count
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
class Voucher extends BaseModel
{
    use HasLogs, HasFormattedTimestamps, HasDbTokens;

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

    public const EVENT_SHARED_BY_EMAIL = 'shared_by_email';
    public const EVENT_PHYSICAL_CARD_REQUESTED = 'physical_card_requested';

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
    public function backoffice_log_eligible(): BelongsTo
    {
        return $this->belongsTo(FundBackofficeLog::class, 'fund_backoffice_log_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     * @noinspection PhpUnused
     */
    public function identity(): BelongsTo
    {
        return $this->belongsTo(Identity::class, 'identity_address', 'address');
    }

    /**
     * @return HasMany
     * @noinspection PhpUnused
     */
    public function reimbursements(): HasMany
    {
        return $this->hasMany(Reimbursement::class);
    }

    /**
     * @return HasMany
     * @noinspection PhpUnused
     */
    public function reimbursements_pending(): HasMany
    {
        return $this->hasMany(Reimbursement::class)->where([
            'state' => Reimbursement::STATE_PENDING,
        ]);
    }

    /**
     * @return HasMany
     * @noinspection PhpUnused
     */
    public function backoffice_logs(): HasMany
    {
        return $this->hasMany(FundBackofficeLog::class);
    }

    /**
     * @return HasOne
     * @noinspection PhpUnused
     */
    public function backoffice_log_received(): HasOne
    {
        return $this->hasOne(FundBackofficeLog::class)->where([
            'action' => BackofficeApi::ACTION_REPORT_RECEIVED,
        ]);
    }

    /**
     * @return HasOne
     * @noinspection PhpUnused
     */
    public function backoffice_log_first_use(): HasOne
    {
        return $this->hasOne(FundBackofficeLog::class)->where([
            'action' => BackofficeApi::ACTION_REPORT_FIRST_USE,
        ]);
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
        /** @var Builder|SoftDeletes|BelongsTo $relationQuery */
        $relationQuery = $this->belongsTo(Product::class, 'product_id', 'id');

        return $relationQuery->withTrashed();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     * @noinspection PhpUnused
     */
    public function transactions(): HasMany
    {
        return $this
            ->hasMany(VoucherTransaction::class)
            ->whereIn('target', VoucherTransaction::TARGETS_OUTGOING);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     * @noinspection PhpUnused
     */
    public function all_transactions(): HasMany
    {
        return $this
            ->hasMany(VoucherTransaction::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     * @noinspection PhpUnused
     */
    public function top_up_transactions(): HasMany
    {
        return $this
            ->hasMany(VoucherTransaction::class)
            ->where('target', VoucherTransaction::TARGET_TOP_UP);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     * @noinspection PhpUnused
     */
    public function last_transaction(): HasOne {
        return $this->hasOne(VoucherTransaction::class)
            ->whereIn('target', VoucherTransaction::TARGETS_OUTGOING)
            ->orderByDesc('created_at');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function product_vouchers(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->where(function(Builder $builder) {
            VoucherQuery::whereIsProductVoucher($builder);
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
     * @return bool
     * @noinspection PhpUnused
     */
    public function getIsExternalAttribute(): bool
    {
        return $this->isExternal();
    }

    /**
     * @return bool
     */
    public function isExternal(): bool
    {
        return $this->fund->fund_config->usesExternalVouchers();
    }

    /**
     * @return bool
     */
    public function isInternal(): bool
    {
        return !$this->fund->fund_config->usesExternalVouchers();
    }

    /**
     * @return string
     * @noinspection PhpUnused
     */
    public function getAmountTopUpAttribute(): string
    {
        return currency_format($this->top_up_transactions()->sum('amount'));
    }

    /**
     * @return string
     * @noinspection PhpUnused
     */
    public function getAmountTopUpCachedAttribute(): string
    {
        return currency_format($this->top_up_transactions->sum('amount'));
    }

    /**
     * @return string
     * @noinspection PhpUnused
     */
    public function getAmountTotalAttribute(): string
    {
        return currency_format(floatval($this->amount) + floatval($this->amount_top_up));
    }

    /**
     * @return string
     * @noinspection PhpUnused
     */
    public function getAmountTotalCachedAttribute(): string
    {
        return currency_format(floatval($this->amount) + floatval($this->amount_top_up_cached));
    }

    /**
     * @return string
     * @noinspection PhpUnused
     */
    public function getAmountSpentAttribute(): string
    {
        return currency_format(array_sum([
            $this->transactions()->sum('amount'),
            $this->product_vouchers()->sum('amount'),
            $this->reimbursements_pending()->sum('amount'),
        ]));
    }

    /**
     * @return string
     * @noinspection PhpUnused
     */
    public function getAmountSpentCachedAttribute(): string
    {
        return currency_format(array_sum([
            $this->transactions->sum('amount'),
            $this->product_vouchers->sum('amount'),
            $this->reimbursements_pending->sum('amount'),
        ]));
    }

    /**
     * @return string
     * @noinspection PhpUnused
     */
    public function getAmountAvailableAttribute(): string
    {
        return currency_format($this->amount_total - $this->amount_spent);
    }

    /**
     * @return string
     * @noinspection PhpUnused
     */
    public function getAmountAvailableCachedAttribute(): string
    {
        return currency_format($this->amount_total_cached - $this->amount_spent_cached);
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
            'need_confirmation' => true,
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
     * @noinspection PhpUnused
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
        return $this->type === 'product' ?
            $this->transactions->count() > 0 :
            floatval($this->amount_available_cached) === 0.0;
    }

    /**
     * @return Carbon|null
     * @noinspection PhpUnused
     */
    public function getLastActiveDayAttribute(): ?Carbon
    {
        return $this->expire_at;
    }

    /**
     * @param string|null $email
     */
    public function sendToEmail(string $email = null): void
    {
        VoucherSendToEmailEvent::dispatch($this, $email);
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
     * @param Request $request
     * @return Builder|Voucher
     */
    public static function search(Request $request): Builder|Voucher
    {
        /** @var Builder|Voucher $query */
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
     * @param BaseFormRequest $request
     * @param Organization $organization
     * @param Fund|null $fund
     * @return Builder
     * @throws \Exception
     */
    public static function searchSponsorQuery(
        BaseFormRequest $request,
        Organization $organization,
        Fund $fund = null
    ): Builder {
        $query = VoucherQuery::whereVisibleToSponsor(self::search($request));
        $unassignedOnly = $request->input('unassigned');
        $in_use = $request->input('in_use');
        $expired = $request->input('expired');
        $count_per_identity_min = $request->input('count_per_identity_min');
        $count_per_identity_max = $request->input('count_per_identity_max');
        $state = $request->input('state');
        $in_use_from = $request->input('in_use_from');
        $in_use_to = $request->input('in_use_to');

        $query->whereHas('fund', static function(Builder $query) use ($organization, $fund) {
            $query->where('organization_id', $organization->id);

            if ($fund) {
                $query->where('id', $fund->id);
            }
        });

        if ($state == 'expired') {
            VoucherQuery::whereExpired($query);
        }

        if ($state && $state != 'expired') {
            VoucherQuery::whereNotExpired($query->where('state', $state));
        }

        if ($unassignedOnly) {
            $query->whereNull('identity_address');
        } else if ($unassignedOnly !== null) {
            $query->whereNotNull('identity_address');
        }

        switch ($request->input('type')) {
            case 'all': break;
            case 'fund_voucher': $query->whereNull('product_id'); break;
            case 'product_voucher': $query->whereNotNull('product_id'); break;
            default: abort(403);
        }

        switch ($request->input('source', 'employee')) {
            case 'all': break;
            case 'user': $query->whereNull('employee_id'); break;
            case 'employee': $query->whereNotNull('employee_id'); break;
            default: abort(403);
        }

        if ($request->has('email') && $email = $request->input('email')) {
            $query->where('identity_address', Identity::findByEmail($email)?->address ?: '_');
        }

        if ($request->input('identity_address', false)) {
            $query->where('identity_address', $request->input('identity_address'));
        }

        if ($request->has('bsn') && $bsn = $request->input('bsn')) {
            $query->where(static function(Builder $builder) use ($bsn, $request) {
                $builder->where('identity_address', Identity::findByBsn($bsn)?->address ?: '-');
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
            $query->where(function(Builder $builder) use ($in_use) {
                if ($in_use) {
                    VoucherQuery::whereInUseQuery($builder);
                } else {
                    VoucherQuery::whereNotInUseQuery($builder);
                }
            });
        }

        if ($count_per_identity_min) {
            $query->whereHas('identity.vouchers', function(Builder $builder) use ($query) {
                $builder->whereIn('vouchers.id', (clone $query)->select('vouchers.id'));
            }, '>=', $count_per_identity_min);
        }

        if ($count_per_identity_max) {
            $query->whereHas('identity.vouchers', function(Builder $builder) use ($query) {
                $builder->whereIn('vouchers.id', (clone $query)->select('vouchers.id'));
            }, '<=', $count_per_identity_max);
        }

        if ($in_use_from || $in_use_to) {
            $query = VoucherQuery::whereInUseDateQuery(
                VoucherSubQuery::appendFirstUseFields($query),
                $in_use_from ? Carbon::parse($in_use_from)->startOfDay() : null,
                $in_use_to ? Carbon::parse($in_use_to)->endOfDay() : null,
            );
        }

        return $query->orderBy(
            $request->input('sort_by', 'created_at'),
            $request->input('sort_order', 'asc')
        );
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
        return $this->usedCount('transactions', false);
    }

    /**
     * @return bool
     * @noinspection PhpUnused
     */
    public function getHasReservationsAttribute(): bool
    {
        return $this->usedCount('reservations', false);
    }

    /**
     * @return bool
     * @noinspection PhpUnused
     */
    public function getInUseAttribute(): bool
    {
        return $this->first_use_date !== null;
    }

    /**
     * @return \Illuminate\Support\Carbon|null
     * @noinspection PhpUnused
     */
    public function getFirstUseDateAttribute(): ?Carbon
    {
        if (key_exists('first_use_date', $this->attributes)) {
            return $this->attributes['first_use_date'] ? Carbon::parse(
                $this->attributes['first_use_date']
            ) : null;
        }

        $model = VoucherSubQuery::appendFirstUseFields(static::query())->find($this->id);
        $this->setAttribute('first_use_date', $model?->getAttribute('first_use_date'));

        return $this->getAttribute('first_use_date');
    }

    /**
     * Assign voucher to identity
     *
     * @param Identity $identity
     * @return $this
     */
    public function assignToIdentity(Identity $identity): self
    {
        $this->update([
            'identity_address' => $identity->address,
            'state' => self::STATE_ACTIVE,
        ]);

        VoucherAssigned::dispatch($this);

        return $this;
    }

    /**
     * @param Product $product
     * @param ProductReservation|null $productReservation
     * @return Voucher
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
            'product_reservation_id'    => $productReservation?->id,
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
     * @return void
     */
    public function setPending(): void
    {
        $this->update(([
            'state' => static::STATE_PENDING,
        ]));
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
     * @param array $extraData
     * @return ProductReservation
     * @throws \Exception
     */
    public function reserveProduct(
        Product $product,
        ?Employee $employee = null,
        array $extraData = []
    ): ProductReservation {
        $isSubsidy = $this->fund->isTypeSubsidy();
        $fundProviderProduct = $product->getFundProviderProduct($this->fund);
        $amount = $isSubsidy && $fundProviderProduct ? $fundProviderProduct->amount : $product->price;

        /** @var ProductReservation $reservation */
        $reservation = $this->product_reservations()->create(array_merge([
            'code'                      => ProductReservation::makeCode(),
            'amount'                    => $amount,
            'state'                     => ProductReservation::STATE_PENDING,
            'product_id'                => $product->id,
            'employee_id'               => $employee?->id,
            'fund_provider_product_id'  => $fundProviderProduct?->id,
            'expire_at'                 => $this->calcExpireDateForProduct($product),
        ], array_only($extraData, [
            'first_name', 'last_name', 'user_note', 'note',
        ]), $product->only('price', 'price_type', 'price_discount')));

        $reservation->makeVoucher();

        ProductReservationCreated::dispatch($reservation);

        return $reservation;
    }

    /**
     * @param array $data
     * @return Reimbursement
     * @throws \Exception
     */
    public function makeReimbursement(array $data = []): Reimbursement
    {
        $submitNow = Arr::get($data, 'state') === Reimbursement::STATE_PENDING;

        /** @var Reimbursement $reimbursement */
        $reimbursement = $this->reimbursements()->create(array_merge([
            'code'          => Reimbursement::makeCode(),
            'state'         => Reimbursement::STATE_DRAFT,
            'submitted_at'  => $submitNow ? now() : null,
        ], array_only($data, [
            'title', 'description', 'amount', 'email', 'iban', 'iban_name', 'state',
        ])));

        ReimbursementCreated::dispatch($reimbursement);

        if ($submitNow && $reimbursement->isPending()) {
            ReimbursementSubmitted::dispatch($reimbursement);
        }

        return $reimbursement;
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
     * @param Collection|Voucher[] $vouchers
     * @param array $fields
     * @param string $dataFormat
     * @param string|null $qrFormat
     * @return array
     */
    public static function exportData(
        Collection|Arrayable $vouchers,
        array $fields,
        string $dataFormat,
        ?string $qrFormat = null
    ): array {
        $data = [];

        $domPdf = resolve('dompdf.wrapper');
        $zipFile = tmpfile();
        $zipFilePath = stream_get_meta_data($zipFile)['uri'];

        $zip = new ZipArchive();
        $zip->open($zipFilePath, ZipArchive::CREATE);

        if ($qrFormat === 'png') {
            $zip->addEmptyDir('images');
        }

        foreach ($vouchers as $voucher) {
            do {
                $voucherData = new VoucherExportData($voucher, $fields, empty($qrFormat));
            } while(in_array($voucherData->getName(), Arr::pluck($data, 'name'), true));

            $dataItem = $data[] = [
                'name' => $voucherData->getName(),
                'value' => $voucher->token_without_confirmation->address,
                'values' => $voucherData->toArray(),
                'voucherData' => $voucherData,
            ];

            if (in_array($qrFormat, ['png', 'all'])) {
                $pngPath = sprintf("images/%s.png", $dataItem['name']);
                $pngData = make_qr_code('voucher', $dataItem['value']);

                $zip->addFromString($pngPath, $pngData);
            }
        }

        if (in_array($qrFormat, ['pdf', 'all'])) {
            $domPdfFile = $domPdf->loadView('pdf.vouchers_export', [
                'vouchersData' => Arr::pluck($data, 'voucherData'),
            ]);

            $zip->addFromString('qr_codes.pdf', $domPdfFile->output());
        }

        $export = new VoucherExport(Arr::pluck($data, 'values'));
        $exportName = 'qr_codes.';
        $files = [];

        if ($dataFormat === 'xls' || $dataFormat === 'all') {
            $files['xls'] = Excel::raw($export, ExcelModel::XLS);
            $zip->addFromString($exportName . 'xls', $files['xls']);
        }

        if ($dataFormat === 'csv' || $dataFormat === 'all') {
            $files['csv'] = Excel::raw($export, ExcelModel::CSV);
            $zip->addFromString($exportName . 'csv', $files['csv']);
        }

        $zip->close();

        $data = array_map(function($item) use ($qrFormat) {
            return array_merge($item['values'], array_only($item, $qrFormat == 'data' ? [
                'name', 'value',
            ] : []));
        }, $data);

        $files['zip'] = file_get_contents($zipFilePath);

        return compact('files', 'data');
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
    public function setBsnRelation(string $bsn): VoucherRelation|Model
    {
        $this->voucher_relation()->delete();

        /** @var VoucherRelation $voucher_relation */
        return $this->voucher_relation()->create(compact('bsn'));
    }

    /**
     * @param Identity $identity
     * @return int|null
     */
    public static function assignAvailableToIdentityByBsn(Identity $identity): ?int
    {
        if (!$identity->bsn) {
            return null;
        }

        /** @var Builder $query */
        $query = self::whereNull('identity_address');

        return $query->whereHas('fund.organization', function(Builder $builder) {
            $builder->where('bsn_enabled', true);
        })->whereHas('voucher_relation', static function(Builder $builder) use ($identity) {
            $builder->where('bsn', '=', $identity->bsn);
        })->get()->each(static function(Voucher $voucher) {
            $voucher->voucher_relation->assignByBsnIfExists();
        })->count();
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
            $activation_code = self::makeUniqueTokenCallback(function ($value) {
                return
                    Prevalidation::whereUid($value)->doesntExist() &&
                    Voucher::whereActivationCode('activation_code')->doesntExist();
            }, 4, 2);
        }

        if (!is_null($activation_code_uid) && $oldVoucher = $queryUsed->first()) {
            $this->assignToIdentity($oldVoucher->identity);
        }

        return $this->updateModel([
            'activation_code' => $activation_code,
            'activation_code_uid' => $activation_code_uid,
        ]);
    }

    /**
     * @param string $code
     * @return PhysicalCard|BaseModel
     */
    public function addPhysicalCard(string $code): PhysicalCard|Model
    {
        return $this->physical_cards()->create([
            'code' => $code,
            'identity_address' => $this->identity_address,
        ]);
    }

    /**
     * @param array $options
     * @param bool $shouldNotifyRequester
     * @return PhysicalCardRequest|Model
     */
    public function makePhysicalCardRequest(
        array $options,
        bool $shouldNotifyRequester = false
    ): PhysicalCardRequest|Model {
        $cardRequest = $this->physical_card_requests()->create(Arr::only($options, [
            'address', 'house', 'house_addition', 'postcode', 'city', 'employee_id',
        ]));

        VoucherPhysicalCardRequestedEvent::broadcast($this, $cardRequest, $shouldNotifyRequester);

        return $cardRequest;
    }

    /**
     * @return bool
     */
    public function needsTransactionReview(): bool
    {
        return $this->hasTransactionsWithin(Config::get('forus.transactions.soft_limit'));
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
            'sponsor' => $this->fund->organization,
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
    public function requesterHistoryLogs(): Collection
    {
        return $this->logs->sortBy('created_at')->whereIn('event', array_merge([
            self::EVENT_EXPIRED_PRODUCT,
            self::EVENT_EXPIRED_BUDGET,
            self::EVENT_DEACTIVATED,
            self::EVENT_ACTIVATED,
        ], self::EVENTS_CREATED));
    }

    /**
     * @return Carbon|null
     */
    public function deactivationDate(): ?Carbon
    {
        return $this->last_deactivation_log?->created_at;
    }

    /**
     * @return FundBackofficeLog|null
     */
    public function reportBackofficeReceived(): ?FundBackofficeLog
    {
        $voucherShouldReport =
            !$this->parent_id &&
            $this->identity_address &&
            !$this->backoffice_log_received()->exists();

        if ($voucherShouldReport) {
            $backOffice = $this->fund->getBackofficeApi();
            $eligibilityLog = $this->backoffice_log_eligible;
            $bsn = $this->identity?->bsn;

            if ($backOffice && $bsn) {
                $requestId = $eligibilityLog->response_id ?? null;
                $backofficeLog = $backOffice->reportReceived($bsn, $requestId);
                $backofficeLog->updateModel(['voucher_id' => $this->id]);

                return $backofficeLog;
            }
        }

        return null;
    }

    /**
     * @return FundBackofficeLog|null
     */
    public function reportBackofficeFirstUse(): ?FundBackofficeLog
    {
        $voucherShouldReport =
            !$this->parent_id &&
            $this->identity_address &&
            !$this->backoffice_log_first_use()->exists();

        if ($voucherShouldReport) {
            $backOffice = $this->fund->getBackofficeApi();
            $firstUseLog = $this->backoffice_log_first_use;
            $bsn = $this->identity?->bsn;

            if ($backOffice && $bsn) {
                $requestId = $firstUseLog->response_id ?? null;
                $backofficeLog = $backOffice->reportFirstUse($bsn, $requestId);
                $backofficeLog->update(['voucher_id' => $this->id]);

                return $backofficeLog;
            }
        }

        return null;
    }

    /**
     * @param string $scope
     * @param bool $fresh
     * @return int
     */
    public function usedCount(string $scope = 'all', bool $fresh = true): int
    {
        $transactions_count = $fresh ? $this->transactions()->count() : $this->transactions->count();
        $reservations_count = $fresh ? $this->product_vouchers()->count() : $this->product_vouchers->count();

        return (in_array($scope, ['all', 'transactions']) ? $transactions_count : 0) +
            (in_array($scope, ['all', 'reservations']) ? $reservations_count : 0);
    }

    /**
     * @param array $attributes
     * @param bool $reviewRequired
     * @return VoucherTransaction
     */
    public function makeTransaction(
        array $attributes = [],
        bool $reviewRequired = false
    ): VoucherTransaction {
        $data = array_merge([
            'state' => VoucherTransaction::STATE_PENDING,
            'address' => resolve('token_generator')->address(),
            'initiator' => VoucherTransaction::INITIATOR_PROVIDER,
            'voucher_id' => $this->id,
        ], $attributes);

        return VoucherTransaction::create(array_merge($data, $reviewRequired ? [
            'attempts' => 50,
            'last_attempt_at' => now(),
        ] : []));
    }

    /**
     * @param string $target_iban
     * @param string $target_name
     * @param Employee $employee
     * @return VoucherTransaction
     */
    public function makeDirectPayment(
        string $target_iban,
        string $target_name,
        Employee $employee,
    ): VoucherTransaction {
        $transaction = $this->makeTransaction([
            'amount' => $this->amount_available,
            'initiator' => VoucherTransaction::INITIATOR_SPONSOR,
            'employee_id' => $employee->id,
            'target' => VoucherTransaction::TARGET_IBAN,
            'target_iban' => $target_iban,
            'target_name' => $target_name,
        ]);

        VoucherTransactionCreated::dispatch($transaction);

        return $transaction;
    }

    /**
     * @param int|null $seconds
     * @return Relation|null
     */
    protected function transactionsWithinQuery(?int $seconds): ?Relation
    {
        if (!is_null($seconds)) {
            return $this->transactions()->where('created_at', '>=', now()->subSeconds($seconds));
        }

        return null;
    }

    /**
     * @param int|null $seconds
     * @return bool|null
     */
    public function hasTransactionsWithin(?int $seconds): ?bool
    {
        return $seconds ? $this->transactionsWithinQuery($seconds)?->exists() : null;
    }
}
