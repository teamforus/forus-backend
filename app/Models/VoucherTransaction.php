<?php

namespace App\Models;

use App\Scopes\Builders\VoucherTransactionQuery;
use App\Searches\VoucherTransactionsSearch;
use App\Services\EventLogService\Traits\HasLogs;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

/**
 * App\Models\VoucherTransaction.
 *
 * @property int $id
 * @property string|null $uid
 * @property int $voucher_id
 * @property int|null $organization_id
 * @property int|null $employee_id
 * @property string|null $description
 * @property int|null $upload_batch_id
 * @property string|null $branch_id
 * @property string|null $branch_name
 * @property string|null $branch_number
 * @property int|null $reimbursement_id
 * @property int|null $product_id
 * @property int|null $fund_provider_product_id
 * @property int|null $voucher_transaction_bulk_id
 * @property string $amount
 * @property string|null $amount_voucher
 * @property string|null $amount_extra_cash
 * @property string|null $iban_from
 * @property string|null $iban_to
 * @property string|null $iban_to_name
 * @property string|null $payment_time
 * @property string $address
 * @property \Illuminate\Support\Carbon|null $transfer_at
 * @property string|null $canceled_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int|null $payment_id
 * @property string $payment_description
 * @property int $attempts
 * @property string $state
 * @property string $initiator
 * @property string $target
 * @property string|null $target_iban
 * @property string|null $target_name
 * @property int|null $target_reimbursement_id
 * @property string|null $last_attempt_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\Employee|null $employee
 * @property-read \App\Models\FundProviderProduct|null $fund_provider_product
 * @property-read string $bulk_status_locale
 * @property-read bool $iban_final
 * @property-read \Carbon\Carbon|null $non_cancelable_at
 * @property-read string $state_locale
 * @property-read string $target_locale
 * @property-read float $transaction_cost
 * @property-read Collection|\App\Services\EventLogService\Models\EventLog[] $logs
 * @property-read int|null $logs_count
 * @property-read Collection|\App\Models\VoucherTransactionNote[] $notes
 * @property-read int|null $notes_count
 * @property-read Collection|\App\Models\VoucherTransactionNote[] $notes_provider
 * @property-read int|null $notes_provider_count
 * @property-read Collection|\App\Models\VoucherTransactionNote[] $notes_sponsor
 * @property-read int|null $notes_sponsor_count
 * @property-read Collection|\App\Models\PayoutRelation[] $payout_relations
 * @property-read int|null $payout_relations_count
 * @property-read \App\Models\Product|null $product
 * @property-read \App\Models\ProductCategory|null $product_category
 * @property-read \App\Models\ProductReservation|null $product_reservation
 * @property-read \App\Models\Organization|null $provider
 * @property-read \App\Models\BusinessType|null $provider_business_type
 * @property-read Collection|\App\Models\Office[] $provider_offices
 * @property-read int|null $provider_offices_count
 * @property-read \App\Models\Reimbursement|null $reimbursement
 * @property-read \App\Models\Voucher $voucher
 * @property-read \App\Models\VoucherTransactionBulk|null $voucher_transaction_bulk
 * @method static Builder<static>|VoucherTransaction newModelQuery()
 * @method static Builder<static>|VoucherTransaction newQuery()
 * @method static Builder<static>|VoucherTransaction onlyTrashed()
 * @method static Builder<static>|VoucherTransaction query()
 * @method static Builder<static>|VoucherTransaction whereAddress($value)
 * @method static Builder<static>|VoucherTransaction whereAmount($value)
 * @method static Builder<static>|VoucherTransaction whereAmountExtraCash($value)
 * @method static Builder<static>|VoucherTransaction whereAmountVoucher($value)
 * @method static Builder<static>|VoucherTransaction whereAttempts($value)
 * @method static Builder<static>|VoucherTransaction whereBranchId($value)
 * @method static Builder<static>|VoucherTransaction whereBranchName($value)
 * @method static Builder<static>|VoucherTransaction whereBranchNumber($value)
 * @method static Builder<static>|VoucherTransaction whereCanceledAt($value)
 * @method static Builder<static>|VoucherTransaction whereCreatedAt($value)
 * @method static Builder<static>|VoucherTransaction whereDeletedAt($value)
 * @method static Builder<static>|VoucherTransaction whereDescription($value)
 * @method static Builder<static>|VoucherTransaction whereEmployeeId($value)
 * @method static Builder<static>|VoucherTransaction whereFundProviderProductId($value)
 * @method static Builder<static>|VoucherTransaction whereIbanFrom($value)
 * @method static Builder<static>|VoucherTransaction whereIbanTo($value)
 * @method static Builder<static>|VoucherTransaction whereIbanToName($value)
 * @method static Builder<static>|VoucherTransaction whereId($value)
 * @method static Builder<static>|VoucherTransaction whereInitiator($value)
 * @method static Builder<static>|VoucherTransaction whereLastAttemptAt($value)
 * @method static Builder<static>|VoucherTransaction whereOrganizationId($value)
 * @method static Builder<static>|VoucherTransaction wherePaymentDescription($value)
 * @method static Builder<static>|VoucherTransaction wherePaymentId($value)
 * @method static Builder<static>|VoucherTransaction wherePaymentTime($value)
 * @method static Builder<static>|VoucherTransaction whereProductId($value)
 * @method static Builder<static>|VoucherTransaction whereReimbursementId($value)
 * @method static Builder<static>|VoucherTransaction whereState($value)
 * @method static Builder<static>|VoucherTransaction whereTarget($value)
 * @method static Builder<static>|VoucherTransaction whereTargetIban($value)
 * @method static Builder<static>|VoucherTransaction whereTargetName($value)
 * @method static Builder<static>|VoucherTransaction whereTargetReimbursementId($value)
 * @method static Builder<static>|VoucherTransaction whereTransferAt($value)
 * @method static Builder<static>|VoucherTransaction whereUid($value)
 * @method static Builder<static>|VoucherTransaction whereUpdatedAt($value)
 * @method static Builder<static>|VoucherTransaction whereUploadBatchId($value)
 * @method static Builder<static>|VoucherTransaction whereVoucherId($value)
 * @method static Builder<static>|VoucherTransaction whereVoucherTransactionBulkId($value)
 * @method static Builder<static>|VoucherTransaction withTrashed()
 * @method static Builder<static>|VoucherTransaction withoutTrashed()
 * @mixin \Eloquent
 */
class VoucherTransaction extends BaseModel
{
    use HasLogs;
    use SoftDeletes;

    public const float TRANSACTION_COST_OLD = .11;

    public const string EVENT_UPDATED = 'updated';
    public const string EVENT_CANCELED_SPONSOR = 'canceled_sponsor';
    public const string EVENT_CANCELED_PROVIDER = 'canceled_provider';
    public const string EVENT_TRANSFER_DELAY_SKIPPED = 'transfer_delay_skipped';

    public const string STATE_PENDING = 'pending';
    public const string STATE_SUCCESS = 'success';
    public const string STATE_CANCELED = 'canceled';

    public const array STATES = [
        self::STATE_PENDING,
        self::STATE_SUCCESS,
        self::STATE_CANCELED,
    ];

    public const string INITIATOR_SPONSOR = 'sponsor';
    public const string INITIATOR_PROVIDER = 'provider';

    public const array INITIATORS = [
        self::INITIATOR_SPONSOR,
        self::INITIATOR_PROVIDER,
    ];

    public const string TARGET_PROVIDER = 'provider';
    public const string TARGET_TOP_UP = 'top_up';
    public const string TARGET_PAYOUT = 'payout';
    public const string TARGET_IBAN = 'iban';

    public const array TARGETS = [
        self::TARGET_PROVIDER,
        self::TARGET_PAYOUT,
        self::TARGET_TOP_UP,
        self::TARGET_IBAN,
    ];

    public const array TARGETS_OUTGOING = [
        self::TARGET_PROVIDER,
        self::TARGET_PAYOUT,
        self::TARGET_IBAN,
    ];

    public const array TARGETS_INCOMING = [
        self::TARGET_TOP_UP,
    ];

    public const array SORT_BY_FIELDS = [
        'id', 'amount', 'created_at', 'state', 'transfer_in', 'fund_name',
        'provider_name', 'product_name', 'target', 'uid', 'date_non_cancelable', 'bulk_state', 'bulk_id',
        'payment_type', 'employee_email', 'relation', 'transfer_at', 'target_iban', 'description',
    ];

    protected $perPage = 25;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'voucher_id', 'organization_id', 'product_id', 'fund_provider_product_id',
        'address', 'amount', 'amount_voucher', 'state', 'payment_id', 'attempts', 'last_attempt_at',
        'iban_from', 'iban_to', 'iban_to_name', 'payment_time', 'employee_id', 'transfer_at',
        'voucher_transaction_bulk_id', 'payment_description', 'initiator', 'reimbursement_id',
        'target', 'target_iban', 'target_name', 'target_reimbursement_id', 'uid',
        'branch_id', 'branch_name', 'branch_number', 'upload_batch_id', 'description',
        'amount_extra_cash',
    ];

    protected $hidden = [
        'voucher_id', 'last_attempt_at', 'attempts', 'notes',
    ];

    protected $casts = [
        'transfer_at' => 'datetime',
        'non_cancelable_at' => 'datetime',
    ];

    /**
     * @return Carbon|null
     * @noinspection PhpUnused
     */
    public function getNonCancelableAtAttribute(): ?Carbon
    {
        return $this->product_reservation ? $this->transfer_at?->clone() : $this->created_at->clone();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     * @noinspection PhpUnused
     */
    public function voucher_transaction_bulk(): BelongsTo
    {
        return $this->belongsTo(VoucherTransactionBulk::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function provider(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'organization_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function voucher(): BelongsTo
    {
        return $this->belongsTo(Voucher::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function reimbursement(): BelongsTo
    {
        return $this->belongsTo(Reimbursement::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function product_reservation(): HasOne
    {
        return $this->hasOne(ProductReservation::class);
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
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     * @noinspection PhpUnused
     */
    public function notes(): HasMany
    {
        return $this->hasMany(VoucherTransactionNote::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     * @noinspection PhpUnused
     */
    public function payout_relations(): HasMany
    {
        return $this->hasMany(PayoutRelation::class)->orderBy('type');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     * @noinspection PhpUnused
     */
    public function notes_sponsor(): HasMany
    {
        return $this->hasMany(VoucherTransactionNote::class)->where(function (Builder $builder) {
            $builder->where('group', 'sponsor');
            $builder->orWhere('shared', true);
        });
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     * @noinspection PhpUnused
     */
    public function notes_provider(): HasMany
    {
        return $this->hasMany(VoucherTransactionNote::class)->where(function (Builder $builder) {
            $builder->where('group', 'provider');
            $builder->orWhere('shared', true);
        });
    }

    /**
     * @return BelongsTo
     * @noinspection PhpUnused
     */
    public function fund_provider_product(): BelongsTo
    {
        return $this->belongsTo(FundProviderProduct::class);
    }

    /**
     * @return HasOneThrough
     * @noinspection PhpUnused
     */
    public function product_category(): HasOneThrough
    {
        return $this->hasOneThrough(
            ProductCategory::class,
            Product::class,
            'id',
            'id',
            'product_id',
            'product_category_id',
        );
    }

    /**
     * @return HasOneThrough
     * @noinspection PhpUnused
     */
    public function provider_business_type(): HasOneThrough
    {
        return $this->hasOneThrough(
            BusinessType::class,
            Organization::class,
            'id',
            'id',
            'organization_id',
            'business_type_id',
        );
    }

    /**
     * @return HasManyThrough
     * @noinspection PhpUnused
     */
    public function provider_offices(): HasManyThrough
    {
        return $this->hasManyThrough(
            Office::class,
            Organization::class,
            firstKey: 'id',
            localKey: 'organization_id',
        );
    }

    /**
     * @return float
     * @noinspection PhpUnused
     */
    public function getTransactionCostAttribute(): float
    {
        if (!$this->amount || !$this->isPaid() || !$this->isOutgoing()) {
            return 0;
        }

        if ($this->voucher_transaction_bulk) {
            return $this->voucher_transaction_bulk->bank_connection->bank->transaction_cost;
        }

        return self::TRANSACTION_COST_OLD;
    }

    /**
     * @return bool
     * @noinspection PhpUnused
     */
    public function getIbanFinalAttribute(): bool
    {
        return $this->isPaid() || ($this->iban_from && $this->iban_to);
    }

    /**
     * @return string
     * @noinspection PhpUnused
     */
    public function getStateLocaleAttribute(): string
    {
        return [
            static::STATE_PENDING => trans('states.voucher_transactions.pending'),
            static::STATE_SUCCESS => trans('states.voucher_transactions.success'),
            static::STATE_CANCELED => trans('states.voucher_transactions.canceled'),
        ][$this->state] ?? $this->state;
    }

    /**
     * @return string
     * @noinspection PhpUnused
     */
    public function getTargetLocaleAttribute(): string
    {
        return [
            self::TARGET_PROVIDER => trans("transaction.target.$this->target"),
            self::TARGET_TOP_UP => trans("transaction.target.$this->target"),
            self::TARGET_PAYOUT => trans("transaction.target.$this->target"),
            self::TARGET_IBAN => trans("transaction.target.$this->target"),
        ][$this->target] ?? $this->target;
    }

    /**
     * @param Request $request
     * @param Organization $organization
     * @return Builder
     */
    public static function searchSponsor(Request $request, Organization $organization): Builder
    {
        $builder = new VoucherTransactionsSearch($request->only([
            'q', 'targets', 'state', 'from', 'to', 'amount_min', 'amount_max',
            'transfer_in_min', 'transfer_in_max', 'fund_state', 'fund_id',
            'voucher_transaction_bulk_id', 'voucher_id', 'pending_bulking',
            'reservation_voucher_id', 'non_cancelable_from', 'non_cancelable_to', 'bulk_state',
            'identity_address', 'execution_date_from', 'execution_date_to',
        ]), self::query());

        return $builder->searchSponsor($organization);
    }

    /**
     * @param Request $request
     * @param Organization $organization
     * @return Builder
     */
    public static function searchProvider(Request $request, Organization $organization): Builder
    {
        $builder = new VoucherTransactionsSearch([
            ...$request->only([
                'q', 'targets', 'state', 'from', 'to', 'amount_min', 'amount_max',
                'transfer_in_min', 'transfer_in_max', 'fund_state', 'fund_id',
            ]),
            'q_type' => 'provider',
        ], self::query());

        return $builder->searchProvider()->where([
            'organization_id' => $organization->id,
        ]);
    }

    /**
     * @param Voucher $voucher
     * @param Request $request
     * @return Builder
     */
    public static function searchVoucher(Voucher $voucher, Request $request): Builder
    {
        $builder = new VoucherTransactionsSearch($request->only([
            'q', 'targets', 'state', 'from', 'to', 'amount_min', 'amount_max',
            'transfer_in_min', 'transfer_in_max', 'fund_state',
        ]), self::query());

        return $builder->query()->where([
            'voucher_id' => $voucher->id,
        ]);
    }

    /**
     * @return string
     * @noinspection PhpUnused
     */
    public function getBulkStatusLocaleAttribute(): string
    {
        if ($this->voucher_transaction_bulk_id) {
            return "bulk #$this->voucher_transaction_bulk_id";
        }

        if ($this->isPending() && $this->attempts <= 3) {
            $daysBefore = $this->daysBeforeTransaction() ?: 1;
            $shouldDelayTransaction = $this->transfer_at && $this->transfer_at->isAfter(now());

            return implode(' ', [
                'In de wachtrij',
                $shouldDelayTransaction ? sprintf('(%s dagen)', $daysBefore) : '',
            ]);
        }

        return '-';
    }

    /**
     * @param string $group
     * @param string $note
     * @param bool $shared
     * @return VoucherTransactionNote|Model
     */
    public function addNote(
        string $group,
        string $note,
        bool $shared = false,
    ): VoucherTransactionNote|Model {
        return $this->notes()->create([
            'message' => $note,
            'shared' => $shared,
            'group' => $group,
        ]);
    }

    /**
     * @return bool
     */
    public function isPaid(): bool
    {
        return $this->state === self::STATE_SUCCESS;
    }

    /**
     * @param int|null $paymentId
     * @param Carbon|null $paymentDate
     * @return void
     */
    public function setPaid(?int $paymentId, ?Carbon $paymentDate): void
    {
        $this->forceFill([
            'state' => self::STATE_SUCCESS,
            'payment_id' => $paymentId,
            'payment_time' => $paymentDate,
        ])->save();
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
    public function isEditablePayout(): bool
    {
        return
            $this->targetIsPayout() &&
            $this->isPending() &&
            !$this->voucher_transaction_bulk_id;
    }

    /**
     * @return bool
     */
    public function isCancelable(): bool
    {
        return !$this->voucher_transaction_bulk &&
            ($this->isPending()) &&
            ($this->transfer_at && $this->transfer_at->isFuture());
    }

    /**
     * @return bool
     */
    public function isCancelableBySponsor(): bool
    {
        return $this->isCancelable();
    }

    /**
     * @return int|null
     */
    public function daysBeforeTransaction(): ?int
    {
        if (!$this->isPending() || !$this->transfer_at) {
            return null;
        }

        return max(now()->diffInDays($this->transfer_at), 0);
    }

    /**
     * @return $this
     */
    public function setForReview(): self
    {
        return $this->updateModel([
            'attempts' => 50,
            'last_attempt_at' => now(),
        ]);
    }

    /**
     * @param int $maxLength
     * @return string
     */
    public function makePaymentDescription(int $maxLength = 2000): string
    {
        if ($this->targetIsIban()) {
            return trans('bunq.transaction.from_fund', [
                'fund_name' => $this->voucher->fund->name,
                'transaction_id' => $this->id,
            ]);
        }

        if (!$this->provider) {
            Log::channel('bng')->error("Unexpected transaction without provider found $this->id.");

            return trans('bunq.transaction.from_fund', [
                'fund_name' => $this->voucher->fund->name,
                'transaction_id' => $this->id,
            ]);
        }

        $separator = " {$this->provider->bank_separator} ";
        $description = $separator . trim(implode($separator, array_filter([
            $this->provider->bank_transaction_id ? $this->id : null,
            $this->provider->bank_transaction_date ? $this->created_at?->format('Y-m-d') : null,
            $this->provider->bank_transaction_time ? $this->created_at?->format('H:i:s') : null,
            $this->provider->bank_reservation_number ? $this->product_reservation?->code : null,
            $this->provider->bank_branch_number ? $this->employee?->office?->branch_number : null,
            $this->provider->bank_branch_id ? $this->employee?->office?->branch_id : null,
            $this->provider->bank_branch_name ? $this->employee?->office?->branch_name : null,
            $this->provider->bank_fund_name ? $this->voucher?->fund?->name : null,
            $this->provider->bank_reservation_first_name ? $this->product_reservation?->first_name : null,
            $this->provider->bank_reservation_last_name ? $this->product_reservation?->last_name : null,
            $this->provider->bank_note ? $this->notes_provider->first()?->message : null,
        ])));

        return strlen($description) <= $maxLength ? $description : str_limit($description, $maxLength - 3);
    }

    /**
     * Set all transactions with zero amount as paid when is payment time (skip bank payment).
     * @return void
     */
    public static function processZeroAmount(): void
    {
        VoucherTransactionQuery::whereReadyForPayoutAndAmountIsZero(static::query())->update([
            'state' => static::STATE_SUCCESS,
            'payment_time' => now(),
        ]);
    }

    /**
     * @return string|null
     */
    public function getTargetIban(): ?string
    {
        return $this->targetIsProvider() ? $this->provider->iban : $this->target_iban;
    }

    /**
     * @return string|null
     */
    public function getTargetName(): ?string
    {
        if ($this->targetIsProvider()) {
            return $this->provider->name;
        }

        if ($this->targetIsTopUp()) {
            return null;
        }

        return $this->target_name ?: 'Onbekend';
    }

    /**
     * @return bool
     */
    public function targetIsProvider(): bool
    {
        return $this->target === self::TARGET_PROVIDER;
    }

    /**
     * @return bool
     */
    public function targetIsPayout(): bool
    {
        return $this->target === self::TARGET_PAYOUT;
    }

    /**
     * @return bool
     * @noinspection PhpUnused
     */
    public function targetIsIban(): bool
    {
        return in_array($this->target, [self::TARGET_IBAN, self::TARGET_PAYOUT]);
    }

    /**
     * @return bool
     * @noinspection PhpUnused
     */
    public function targetIsTopUp(): bool
    {
        return $this->target === self::TARGET_TOP_UP;
    }

    /**
     * @return bool
     */
    public function isOutgoing(): bool
    {
        return in_array($this->target, self::TARGETS_OUTGOING);
    }

    /**
     * @return bool
     * @noinspection PhpUnused
     */
    public function isIncoming(): bool
    {
        return in_array($this->target, self::TARGETS_INCOMING);
    }

    /**
     * @throws \Random\RandomException
     * @return int
     */
    public static function makeBatchUploadId(): int
    {
        do {
            $batchId = random_int(100_000_000_000, 900_000_000_000);
        } while (VoucherTransaction::whereUploadBatchId($batchId)->exists());

        return $batchId;
    }

    /**
     * @param Employee $employee
     * @param array $data
     * @return void
     */
    public function updatePayout(Employee $employee, array $data): void
    {
        $amount = Arr::get($data, 'amount');
        $voucher = $this->voucher;
        $amountPreset = $voucher->fund->amount_presets?->find(Arr::get($data, 'amount_preset_id'));

        if ($amountPreset) {
            if (($amountPreset->id !== $voucher->fund_amount_preset_id) ||
                ($amountPreset->amount !== $voucher->amount)) {
                $voucher->update([
                    'amount' => $amountPreset->amount,
                    'fund_amount_preset_id' => $amountPreset->id,
                ]);

                $this->update([ 'amount' => $voucher->amount ]);
            }
        }

        if ($amount && ($amount !== $voucher->amount)) {
            if ($amount !== $voucher->amount) {
                $voucher->update([
                    'amount' => $amount,
                    'fund_amount_preset_id' => null,
                ]);

                $this->update([
                    'amount' => $voucher->amount,
                ]);
            }
        }

        $this->update(Arr::only($data, [
            'target_iban', 'target_name', 'description',
        ]));

        $this->log(
            event: self::EVENT_UPDATED,
            models: self::getLogModels($employee),
            identity_address: $employee->identity_address,
        );
    }

    /**
     * @param ?Employee $employee
     * @param bool $sponsor
     * @return VoucherTransaction
     */
    public function cancelPending(?Employee $employee, bool $sponsor): VoucherTransaction
    {
        $this->update([
            'state' => self::STATE_CANCELED,
            'canceled_at' => now(),
        ]);

        $this->log(
            event: $sponsor ? self::EVENT_CANCELED_SPONSOR : self::EVENT_CANCELED_PROVIDER,
            models: $this->getLogModels($employee),
            identity_address: $employee?->identity_address,
        );

        return $this;
    }

    /**
     * @param Employee $employee
     * @return VoucherTransaction
     */
    public function skipTransferDelay(Employee $employee): VoucherTransaction
    {
        $this->update([
            'transfer_at' => now(),
        ]);

        $this->log(
            event: self::EVENT_TRANSFER_DELAY_SKIPPED,
            models: $this->getLogModels($employee),
            identity_address: $employee->identity_address
        );

        return $this;
    }

    /**
     * @param string $type
     * @param string $value
     * @return PayoutRelation
     */
    public function addPayoutRelation(string $type, string $value): PayoutRelation
    {
        return $this->payout_relations()->create([
            'type' => $type,
            'value' => $value,
        ]);
    }

    /**
     * @return VoucherTransaction[]
     */
    protected function getLogModels(?Employee $employee): array
    {
        return [
            'employee' => $employee,
            'voucher_transaction' => $this,
        ];
    }
}
