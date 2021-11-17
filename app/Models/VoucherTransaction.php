<?php

namespace App\Models;

use App\Scopes\Builders\VoucherTransactionQuery;
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
 * @property int $organization_id
 * @property int|null $employee_id
 * @property int|null $product_id
 * @property int|null $fund_provider_product_id
 * @property int|null $voucher_transaction_bulk_id
 * @property string $amount
 * @property string|null $iban_from
 * @property string|null $iban_to
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
 * @property string|null $last_attempt_at
 * @property-read \App\Models\Employee|null $employee
 * @property-read \App\Models\FundProviderProduct|null $fund_provider_product
 * @property-read float $transaction_cost
 * @property-read Collection|\App\Models\VoucherTransactionNote[] $notes
 * @property-read int|null $notes_count
 * @property-read Collection|\App\Models\VoucherTransactionNote[] $notes_provider
 * @property-read int|null $notes_provider_count
 * @property-read Collection|\App\Models\VoucherTransactionNote[] $notes_sponsor
 * @property-read int|null $notes_sponsor_count
 * @property-read \App\Models\Product|null $product
 * @property-read \App\Models\ProductReservation|null $product_reservation
 * @property-read \App\Models\Organization $provider
 * @property-read \App\Models\Voucher $voucher
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
 * @method static Builder|VoucherTransaction whereId($value)
 * @method static Builder|VoucherTransaction whereLastAttemptAt($value)
 * @method static Builder|VoucherTransaction whereOrganizationId($value)
 * @method static Builder|VoucherTransaction wherePaymentDescription($value)
 * @method static Builder|VoucherTransaction wherePaymentId($value)
 * @method static Builder|VoucherTransaction wherePaymentTime($value)
 * @method static Builder|VoucherTransaction whereProductId($value)
 * @method static Builder|VoucherTransaction whereState($value)
 * @method static Builder|VoucherTransaction whereTransferAt($value)
 * @method static Builder|VoucherTransaction whereUpdatedAt($value)
 * @method static Builder|VoucherTransaction whereVoucherId($value)
 * @method static Builder|VoucherTransaction whereVoucherTransactionBulkId($value)
 * @mixin \Eloquent
 */
class VoucherTransaction extends Model
{
    protected $perPage = 25;

    public const STATE_PENDING = 'pending';
    public const STATE_SUCCESS = 'success';
    public const STATE_CANCELED = 'canceled';

    public const STATES = [
        self::STATE_PENDING,
        self::STATE_SUCCESS,
        self::STATE_CANCELED,
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'voucher_id', 'organization_id', 'product_id', 'fund_provider_product_id',
        'address', 'amount', 'state', 'payment_id', 'attempts', 'last_attempt_at',
        'iban_from', 'iban_to', 'payment_time', 'employee_id', 'transfer_at',
        'voucher_transaction_bulk_id', 'payment_description',
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
    public function product(): BelongsTo {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function provider(): BelongsTo {
        return $this->belongsTo(Organization::class, 'organization_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function voucher(): BelongsTo {
        return $this->belongsTo(Voucher::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function product_reservation(): HasOne {
        return $this->hasOne(ProductReservation::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function employee(): BelongsTo {
        return $this->belongsTo(Employee::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function notes(): HasMany {
        return $this->hasMany(VoucherTransactionNote::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function notes_sponsor(): HasMany {
        return $this->hasMany(VoucherTransactionNote::class)->where('group', 'sponsor');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function notes_provider(): HasMany {
        return $this->hasMany(VoucherTransactionNote::class)->where('group', 'provider');
    }

    /**
     * @return BelongsTo
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
        return $this->amount > 0 ? .11 : 0;
    }

    /**
     * @return string
     */
    public function getStateLocaleAttribute(): string
    {
        return [
            static::STATE_PENDING => 'In afwachting',
            static::STATE_SUCCESS => 'Voltooid',
        ][$this->state] ?? $this->state;
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function sendPushNotificationTransaction(): void {
        $mailService = resolve('forus.services.notification');

        // Product voucher
        if (!$this->voucher->product) {
            $transData = [
                "amount" => currency_format_locale($this->amount),
                "fund_name" => $this->voucher->fund->name,
            ];

            $fund = $this->voucher->fund;
            if ($fund->isTypeSubsidy()) {
                $fundProviderProduct = $this->product->getSubsidyDetailsForFund($fund);

                if ($fundProviderProduct && $this->voucher->identity_address) {
                    $transData = array_merge($transData, [
                        "product_name" => $this->product->name,
                        "new_limit"    => $fundProviderProduct->stockAvailableForVoucher($this->voucher),
                    ]);
                }
            }

            $title = trans('push.transactions.offline_regular_voucher.'.$fund->type.'.title', $transData);
            $body = trans('push.transactions.offline_regular_voucher.'.$fund->type.'.body', $transData);
        } else {
            $transData = [
                "product_name" => $this->voucher->product->name,
            ];

            $title = trans('push.transactions.offline_product_voucher.title', $transData);
            $body = trans('push.transactions.offline_product_voucher.body', $transData);
        }

        if ($this->voucher->identity_address) {
            $mailService->sendPushNotification(
                $this->voucher->identity_address, $title, $body, 'voucher.transaction'
            );
        }
    }

    /**
     * @return void
     */
    public function sendPushBunqTransactionSuccess(): void {
        $mailService = resolve('forus.services.notification');
        $transData = [
            "amount" => currency_format_locale($this->amount)
        ];

        $title = trans('push.bunq_transactions.complete.title', $transData);
        $body = trans('push.bunq_transactions.complete.body', $transData);

        $mailService->sendPushNotification(
            $this->provider->identity_address, $title, $body, 'bunq.transaction_success'
        );
    }

    /**
     * @param Request $request
     * @return Builder
     */
    public static function search(Request $request): Builder
    {
        /** @var Builder $query */
        $query = self::query();

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

        return $query->latest();
    }

    /**
     * @param Request $request
     * @param Organization $organization
     * @param ?Fund $fund
     * @param ?Organization $provider
     * @return Builder
     */
    public static function searchSponsor(
        Request $request,
        Organization $organization,
        Fund $fund = null,
        Organization $provider = null
    ): Builder {
        $builder = self::search($request)->whereHas('voucher.fund.organization', function (
            Builder $query
        ) use ($organization) {
            $query->where('id', $organization->id);
        });

        if ($provider) {
            $builder->where('organization_id', $provider->id);
        }

        if ($voucher_transaction_bulk_id = $request->input('voucher_transaction_bulk_id')) {
            $builder->where(compact('voucher_transaction_bulk_id'));
        }

        if ($request->input('pending_bulking')) {
            VoucherTransactionQuery::whereAvailableForBulking($builder);
        }

        if ($fund) {
            $builder->whereHas('voucher', static function (Builder $builder) use ($fund) {
                $builder->where('fund_id', $fund->id);
            });
        }

        return $builder;
    }

    /**
     * @param Request $request
     * @param Organization $organization
     * @return Builder
     */
    public static function searchProvider(
        Request $request,
        Organization $organization
    ): Builder {
        return self::search($request)->where([
            'organization_id' => $organization->id
        ]);
    }

    /**
     * @param Voucher $voucher
     * @param Request $request
     * @return Builder
     */
    public static function searchVoucher(
        Voucher $voucher,
        Request $request
    ): Builder {
        return self::search($request)->where([
            'voucher_id' => $voucher->id
        ]);
    }

    /**
     * @param Builder $builder
     * @return Builder[]|Collection|\Illuminate\Support\Collection
     */
    private static function exportTransform(Builder $builder) {
        $transKey = "export.voucher_transactions";

        return $builder->with([
            'voucher.fund',
            'provider',
        ])->get()->map(static function(
            VoucherTransaction $transaction
        ) use ($transKey) {
            return [
                trans("$transKey.id") => $transaction->id,
                trans("$transKey.amount") => currency_format(
                    $transaction->amount
                ),
                trans("$transKey.date_transaction") => format_datetime_locale($transaction->created_at),
                trans("$transKey.date_payment") => format_datetime_locale($transaction->payment_time),
                trans("$transKey.fund") => $transaction->voucher->fund->name,
                trans("$transKey.provider") => $transaction->provider->name,
                trans("$transKey.state") => trans("$transKey.state-values.$transaction->state"),
            ];
        })->values();
    }

    /**
     * @param Request $request
     * @param Organization $organization
     * @return Builder[]|Collection|\Illuminate\Support\Collection
     */
    public static function exportProvider(
        Request $request,
        Organization $organization
    ) {
        return self::exportTransform(self::searchProvider($request, $organization));
    }

    /**
     * @param Request $request
     * @param Organization $organization
     * @param Fund|null $fund
     * @param Organization|null $provider
     * @return Builder[]|Collection|\Illuminate\Support\Collection
     */
    public static function exportSponsor(
        Request $request,
        Organization $organization,
        Fund $fund = null,
        Organization $provider = null
    ) {
        return self::exportTransform(self::searchSponsor($request, $organization, $fund, $provider));
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
        return ($this->state === $this::STATE_PENDING) &&
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
}
