<?php

namespace App\Models;

use App\Exports\VoucherTransactionsProviderExport;
use App\Exports\VoucherTransactionsSponsorExport;
use App\Scopes\Builders\VoucherTransactionQuery;
use App\Services\EventLogService\Traits\HasLogs;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Http\Request;

/**
 * App\Models\VoucherTransaction
 *
 * @property int $id
 * @property int $voucher_id
 * @property int|null $organization_id
 * @property int|null $employee_id
 * @property int|null $reimbursement_id
 * @property int|null $product_id
 * @property int|null $fund_provider_product_id
 * @property int|null $voucher_transaction_bulk_id
 * @property string $amount
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
 * @property string|null $last_attempt_at
 * @property-read \App\Models\Employee|null $employee
 * @property-read \App\Models\FundProviderProduct|null $fund_provider_product
 * @property-read bool $iban_final
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
 * @property-read \App\Models\Product|null $product
 * @property-read \App\Models\ProductReservation|null $product_reservation
 * @property-read \App\Models\Organization|null $provider
 * @property-read \App\Models\Reimbursement|null $reimbursement
 * @property-read \App\Models\Voucher $voucher
 * @property-read \App\Models\VoucherTransactionBulk|null $voucher_transaction_bulk
 * @method static Builder|VoucherTransaction newModelQuery()
 * @method static Builder|VoucherTransaction newQuery()
 * @method static Builder|VoucherTransaction query()
 * @method static Builder|VoucherTransaction whereAddress($value)
 * @method static Builder|VoucherTransaction whereAmount($value)
 * @method static Builder|VoucherTransaction whereAttempts($value)
 * @method static Builder|VoucherTransaction whereCanceledAt($value)
 * @method static Builder|VoucherTransaction whereCreatedAt($value)
 * @method static Builder|VoucherTransaction whereEmployeeId($value)
 * @method static Builder|VoucherTransaction whereFundProviderProductId($value)
 * @method static Builder|VoucherTransaction whereIbanFrom($value)
 * @method static Builder|VoucherTransaction whereIbanTo($value)
 * @method static Builder|VoucherTransaction whereIbanToName($value)
 * @method static Builder|VoucherTransaction whereId($value)
 * @method static Builder|VoucherTransaction whereInitiator($value)
 * @method static Builder|VoucherTransaction whereLastAttemptAt($value)
 * @method static Builder|VoucherTransaction whereOrganizationId($value)
 * @method static Builder|VoucherTransaction wherePaymentDescription($value)
 * @method static Builder|VoucherTransaction wherePaymentId($value)
 * @method static Builder|VoucherTransaction wherePaymentTime($value)
 * @method static Builder|VoucherTransaction whereProductId($value)
 * @method static Builder|VoucherTransaction whereReimbursementId($value)
 * @method static Builder|VoucherTransaction whereState($value)
 * @method static Builder|VoucherTransaction whereTarget($value)
 * @method static Builder|VoucherTransaction whereTargetIban($value)
 * @method static Builder|VoucherTransaction whereTargetName($value)
 * @method static Builder|VoucherTransaction whereTransferAt($value)
 * @method static Builder|VoucherTransaction whereUpdatedAt($value)
 * @method static Builder|VoucherTransaction whereVoucherId($value)
 * @method static Builder|VoucherTransaction whereVoucherTransactionBulkId($value)
 * @mixin \Eloquent
 */
class VoucherTransaction extends BaseModel
{
    use HasLogs;

    protected $perPage = 25;

    public const EVENT_BUNQ_TRANSACTION_SUCCESS = 'bunq_transaction_success';
    public const TRANSACTION_COST_OLD = .11;

    public const EVENTS = [
        self::EVENT_BUNQ_TRANSACTION_SUCCESS,
    ];

    public const STATE_PENDING = 'pending';
    public const STATE_SUCCESS = 'success';
    public const STATE_CANCELED = 'canceled';

    public const STATES = [
        self::STATE_PENDING,
        self::STATE_SUCCESS,
        self::STATE_CANCELED,
    ];

    public const INITIATOR_SPONSOR = 'sponsor';
    public const INITIATOR_PROVIDER = 'provider';

    public const INITIATORS = [
        self::INITIATOR_SPONSOR,
        self::INITIATOR_PROVIDER,
    ];

    public const TARGET_PROVIDER = 'provider';
    public const TARGET_TOP_UP = 'top_up';
    public const TARGET_IBAN = 'iban';

    public const TARGETS = [
        self::TARGET_PROVIDER,
        self::TARGET_TOP_UP,
        self::TARGET_IBAN,
    ];

    public const TARGETS_OUTGOING = [
        self::TARGET_PROVIDER,
        self::TARGET_IBAN,
    ];

    public const TARGETS_INCOMING = [
        self::TARGET_TOP_UP,
    ];

    public const SORT_BY_FIELDS = [
        'id', 'amount', 'created_at', 'state', 'voucher_transaction_bulk_id',
        'fund_name', 'provider_name', 'target',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'voucher_id', 'organization_id', 'product_id', 'fund_provider_product_id',
        'address', 'amount', 'state', 'payment_id', 'attempts', 'last_attempt_at',
        'iban_from', 'iban_to', 'iban_to_name', 'payment_time', 'employee_id', 'transfer_at',
        'voucher_transaction_bulk_id', 'payment_description', 'initiator',
        'target', 'target_iban', 'target_name', 'reimbursement_id',
    ];

    protected $hidden = [
        'voucher_id', 'last_attempt_at', 'attempts', 'notes',
    ];

    protected $dates = [
        'transfer_at',
    ];

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
    public function notes_sponsor(): HasMany
    {
        return $this->hasMany(VoucherTransactionNote::class)->where('group', 'sponsor');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     * @noinspection PhpUnused
     */
    public function notes_provider(): HasMany
    {
        return $this->hasMany(VoucherTransactionNote::class)->where('group', 'provider');
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
            static::STATE_PENDING => 'In afwachting',
            static::STATE_SUCCESS => 'Voltooid',
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
            self::TARGET_IBAN => trans("transaction.target.$this->target"),
        ][$this->target] ?? $this->target;
    }

    /**
     * @param Request $request
     * @return Builder
     */
    public static function search(Request $request): Builder
    {
        /** @var Builder|VoucherTransaction $query */
        $query = self::query();
        $targets = $request->input('targets', static::TARGETS_OUTGOING);

        if ($request->has('q') && $q = $request->input('q', '')) {
            $query->where(static function (Builder $query) use ($q) {
                $query->whereHas('provider', static function (Builder $query) use ($q) {
                    $query->where('name', 'LIKE', "%$q%");
                });

                $query->orWhereHas('voucher.fund', static function (Builder $query) use ($q) {
                    $query->where('name', 'LIKE', "%$q%");
                });

                $query->orWhere('voucher_transactions.id','LIKE', "%$q%");
            });
        }

        if ($request->has('state') && $state = $request->input('state')) {
            $query->where('state', $state);
        }

        if ($request->has('from') && $from = $request->input('from')) {
            $from = (Carbon::createFromFormat('Y-m-d', $from));

            $query->where(
                'created_at',
                '>=',
                $from->startOfDay()->format('Y-m-d H:i:s')
            );
        }

        if ($request->has('to') && $to = $request->input('to')) {
            $to = (Carbon::createFromFormat('Y-m-d', $to));

            $query->where(
                'created_at',
                '<=',
                $to->endOfDay()->format('Y-m-d H:i:s')
            );
        }

        if ($amount_min = $request->input('amount_min')) {
            $query->where('amount', '>=', $amount_min);
        }

        if ($amount_max = $request->input('amount_max')) {
            $query->where('amount', '<=', $amount_max);
        }

        if ($request->has('fund_state') && $fund_state = $request->input('fund_state')) {
            $query->whereHas('voucher.fund', static function (Builder $query) use ($fund_state) {
                $query->where('state', '=',  $fund_state);
            });
        }

        $query->whereIn('target', is_array($targets) ? $targets : []);

        return $query;
    }

    /**
     * @param Request $request
     * @param Organization $organization
     * @return Builder
     */
    public static function searchSponsor(Request $request, Organization $organization): Builder
    {
        $builder = self::search($request)->whereHas('voucher.fund', function (
            Builder $query
        ) use ($organization, $request) {
            if ($fund_id = $request->input('fund_id')) {
                $query->where('id', $fund_id);
            }

            $query->whereHas('organization', function (Builder $query) use ($organization) {
                $query->where('id', $organization->id);
            });
        });

        if ($voucher_transaction_bulk_id = $request->input('voucher_transaction_bulk_id')) {
            $builder->where(compact('voucher_transaction_bulk_id'));
        }

        if ($voucher_id = $request->input('voucher_id')) {
            $builder->where(compact('voucher_id'));
        }

        if ($request->input('pending_bulking')) {
            VoucherTransactionQuery::whereAvailableForBulking($builder);
        }

        return $builder;
    }

    /**
     * @param Request $request
     * @param Organization $organization
     * @return Builder
     */
    public static function searchProvider(Request $request, Organization $organization): Builder
    {
        $builder = self::search($request)->where([
            'organization_id' => $organization->id,
        ]);

        if ($request->has('fund_id')) {
            $builder->whereRelation('voucher', 'fund_id', $request->get('fund_id'));
        }

        return VoucherTransactionQuery::whereOutgoing($builder);
    }

    /**
     * @param Voucher $voucher
     * @param Request $request
     * @return Builder
     */
    public static function searchVoucher(Voucher $voucher, Request $request): Builder
    {
        return self::search($request)->where([
            'voucher_id' => $voucher->id
        ]);
    }

    /**
     * @param Builder $builder
     * @param array $fields
     * @return Builder[]|Collection|\Illuminate\Support\Collection
     */
    private static function exportTransform(Builder $builder, array $fields)
    {
        $fieldLabels = array_pluck(array_merge(
            VoucherTransactionsSponsorExport::getExportFields(),
            VoucherTransactionsProviderExport::getExportFields()
        ), 'name', 'key');

        $data = $builder->with('voucher.fund', 'provider')->get();

        $data = $data->map(fn(VoucherTransaction $transaction) => array_only([
            'id' => $transaction->id,
            'amount' => currency_format($transaction->amount),
            'date_transaction' => format_datetime_locale($transaction->created_at),
            'date_payment' => format_datetime_locale($transaction->payment_time),
            'fund_name' => $transaction->voucher->fund->name,
            'provider' => $transaction->targetIsProvider() ? $transaction->provider->name : '',
            'state' => trans("export.voucher_transactions.state-values.$transaction->state"),
        ], $fields))->values();

        return $data->map(function($item) use ($fieldLabels) {
            return array_reduce(array_keys($item), fn($obj, $key) => array_merge($obj, [
                $fieldLabels[$key] => $item[$key],
            ]), []);
        });
    }

    /**
     * @param Request $request
     * @param Organization $organization
     * @param array $fields
     * @return Builder[]|Collection|\Illuminate\Support\Collection
     */
    public static function exportProvider(Request $request, Organization $organization, array $fields)
    {
        return self::exportTransform(VoucherTransactionQuery::order(
            self::searchProvider($request, $organization),
            $request->get('order_by'),
            $request->get('order_dir')
        ), $fields);
    }

    /**
     * @param Request $request
     * @param Organization $organization
     * @param array $fields
     * @return Builder[]|Collection|\Illuminate\Support\Collection
     */
    public static function exportSponsor(
        Request $request,
        Organization $organization,
        array $fields
    ) {
        return static::exportTransform(VoucherTransactionQuery::order(
            static::searchSponsor($request, $organization),
            $request->get('order_by'),
            $request->get('order_dir')
        ), $fields);
    }

    /**
     * @param string $group
     * @param string $note
     * @return \Illuminate\Database\Eloquent\Model|VoucherTransactionNote
     */
    public function addNote(string $group, string $note): VoucherTransactionNote
    {
        return $this->notes()->create([
            'message' => $note,
            'group' => $group
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
     * @return bool
     */
    public function isPending(): bool
    {
        return $this->state === self::STATE_PENDING;
    }

    /**
     * @return bool
     */
    public function isCancelable(): bool
    {
        return !$this->voucher_transaction_bulk &&
            ($this->state === $this::STATE_PENDING) &&
            ($this->transfer_at && $this->transfer_at->isFuture());
    }

    /**
     * @return VoucherTransaction
     */
    public function cancelPending(): VoucherTransaction
    {
        return $this->updateModel([
            'state' => self::STATE_CANCELED,
            'canceled_at' => now(),
        ]);
    }

    /**
     * @return int|null
     */
    public function daysBeforeTransaction(): ?int
    {
        if (!$this->isPending() || !$this->transfer_at) {
            return null;
        }

        return max($this->transfer_at->diffInDays(now()), 0);
    }

    /**
     * @return $this
     */
    public function setForReview(): self
    {
        return $this->updateModel([
            'attempts' => 50,
            'last_attempt_at' => now()
        ]);
    }

    /**
     * @return string
     */
    public function makePaymentDescription(): string
    {
        return trans('bunq.transaction.from_fund', [
            'fund_name' => $this->voucher->fund->name,
            'transaction_id' => $this->id
        ]);
    }

    /**
     * Set all transactions with zero amount as paid when is payment time (skip bank payment)
     * @return void
     */
    public static function processZeroAmount(): void
    {
        VoucherTransactionQuery::whereReadyForPayoutAndAmountIsZero(static::query())->update([
            'state'         => static::STATE_SUCCESS,
            'payment_time'  => now(),
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
     * @noinspection PhpUnused
     */
    public function targetIsIban(): bool
    {
        return $this->target === self::TARGET_IBAN;
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
}
