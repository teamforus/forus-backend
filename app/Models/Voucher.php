<?php

namespace App\Models;

use App\Events\Vouchers\ProductVoucherShared;
use App\Events\Vouchers\VoucherAssigned;
use App\Events\Vouchers\VoucherCreated;
use App\Models\Data\VoucherExportData;
use App\Services\EventLogService\Traits\HasLogs;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Http\Request;

/**
 * App\Models\Voucher
 *
 * @property int $id
 * @property int $fund_id
 * @property string|null $identity_address
 * @property float $amount
 * @property bool $returnable
 * @property string|null $note
 * @property int|null $employee_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int|null $product_id
 * @property int|null $parent_id
 * @property \Illuminate\Support\Carbon|null $expire_at
 * @property-read \App\Models\Fund $fund
 * @property-read mixed $amount_available
 * @property-read mixed $amount_available_cached
 * @property-read bool $expired
 * @property-read bool $has_transactions
 * @property-read bool $is_granted
 * @property-read string $type
 * @property-read bool $used
 * @property-read Carbon $last_active_day
 * @property-read \App\Models\Voucher|null $parent
 * @property-read \App\Models\Product|null $product
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\PhysicalCard[] $physical_cards
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Voucher[] $product_vouchers
 * @property-read int|null $product_vouchers_count
 * @property-read \App\Models\VoucherToken $token_with_confirmation
 * @property-read \App\Models\VoucherToken $token_without_confirmation
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\VoucherToken[] $tokens
 * @property-read int|null $tokens_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\VoucherTransaction[] $transactions
 * @property-read int|null $transactions_count
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Voucher newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Voucher newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Voucher query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Voucher whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Voucher whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Voucher whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Voucher whereExpireAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Voucher whereFundId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Voucher whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Voucher whereIdentityAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Voucher whereNote($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Voucher whereParentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Voucher whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Voucher whereReturnable($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Voucher whereUpdatedAt($value)
 * @mixin \Eloquent
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Services\EventLogService\Models\EventLog[] $logs
 * @property-read int|null $logs_count
 * @property-read int|null $physical_cards_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\PhysicalCardRequest[] $physical_card_requests
 * @property-read int|null $physical_card_requests_count
 */
class Voucher extends Model
{
    use HasLogs;

    public const EVENT_CREATED_BUDGET = 'created_budget';
    public const EVENT_CREATED_PRODUCT = 'created_product';
    public const EVENT_SHARED = 'shared';
    public const EVENT_EXPIRED_BUDGET = 'expired';
    public const EVENT_EXPIRED_PRODUCT = 'expired';
    public const EVENT_EXPIRING_SOON_BUDGET = 'expiring_soon_budget';
    public const EVENT_EXPIRING_SOON_PRODUCT = 'expiring_soon_product';
    public const EVENT_ASSIGNED = 'assigned';

    public const EVENT_TRANSACTION = 'transaction';
    public const EVENT_TRANSACTION_PRODUCT = 'transaction_product';

    public const TYPE_BUDGET = 'regular';
    public const TYPE_PRODUCT = 'product';

    public const TYPES = [
        self::TYPE_BUDGET,
        self::TYPE_PRODUCT,
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'fund_id', 'identity_address', 'amount', 'product_id', 'parent_id',
        'expire_at', 'note', 'employee_id', 'returnable',
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'expire_at'
    ];

    protected $casts = [
        'returnable' => 'boolean'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function fund(): BelongsTo {
        return $this->belongsTo(Fund::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function parent(): BelongsTo {
        return $this->belongsTo(Voucher::class, 'parent_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function physical_cards(): HasMany {
        return $this->hasMany(PhysicalCard::class);
    }


    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function physical_card_requests(): HasMany {
        return $this->hasMany(PhysicalCardRequest::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function product(): BelongsTo {
        return query_with_trashed($this->belongsTo(
            Product::class, 'product_id', 'id'
        ));
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function transactions(): HasMany {
        return $this->hasMany(VoucherTransaction::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function product_vouchers(): HasMany {
        return $this->hasMany(Voucher::class, 'parent_id');
    }

    /**
     * @return string
     */
    public function getTypeAttribute(): string {
        return $this->product_id ? 'product' : 'regular';
    }

    public function getAmountAvailableAttribute() {
        return round($this->amount -
            $this->transactions()->sum('amount') -
            $this->product_vouchers()->sum('amount'), 2);
    }

    public function getAmountAvailableCachedAttribute() {
        return round($this->amount -
            $this->transactions->sum('amount') -
            $this->product_vouchers->sum('amount'), 2);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function tokens(): HasMany {
        return $this->hasMany(VoucherToken::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function token_without_confirmation(): HasOne {
        return $this->hasOne(VoucherToken::class)->where([
            'need_confirmation' => false
        ]);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function token_with_confirmation(): HasOne {
        return $this->hasOne(VoucherToken::class)->where([
            'need_confirmation' => true
        ]);
    }

    /**
     * The voucher is expired
     *
     * @return bool
     */
    public function getExpiredAttribute(): bool {
        return (bool) $this->expire_at->isPast();
    }

    /**
     * The voucher is expired
     *
     * @return bool
     */
    public function getUsedAttribute(): bool
    {
        return $this->type === 'product' ? $this->transactions->count() > 0 :
            $this->amount_available_cached === 0;
    }

    /**
     * @return Carbon|\Illuminate\Support\Carbon
     */
    public function getLastActiveDayAttribute() {
        return $this->type === 'product' ?
            $this->product->expire_at : $this->expire_at->subDay();
    }

    /**
     * @param string|null $email
     */
    public function sendToEmail(
        string $email = null
    ): void {
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
            format_date_locale($this->expire_at->subDay(), 'long_date_locale'),
            $fund_product_name,
            $voucherToken->address
        );
    }

    /**
     * @param string|null $email
     */
    public function assignedVoucherEmail(
        string $email = null
    ): void {
        /** @var VoucherToken $voucherToken */
        $voucherToken = $this->tokens()->where([
            'need_confirmation' => false
        ])->first();

        resolve('forus.services.notification')->assignVoucher(
            $email,
            $this->fund->fund_config->implementation->getEmailFrom(),
            $this->fund->name,
            $this->amount,
            format_date_locale($this->expire_at->subDay(), 'long_date_locale'),
            $voucherToken->address
        );
    }

    /**
     * @param string $message
     * @param bool $sendCopyToUser
     */
    public function shareVoucherEmail(
        string $message,
        bool $sendCopyToUser = false
    ): void {
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
        $notificationService = resolve('forus.services.notification');
        $date = now()->addDays($days)->startOfDay()->format('Y-m-d');

        $vouchers = self::query()
            ->whereNull('product_id')
            ->with(['fund', 'fund.organization'])
            ->whereDate('expire_at', '=', $date)
            ->get();

        /** @var self $voucher */
        foreach ($vouchers as $voucher) {
            if ($voucher->amount_available_cached > 0) {
                $recordRepo = resolve('forus.services.record');
                $primaryEmail = $recordRepo->primaryEmailByAddress(
                    $voucher->identity_address
                );

                $fund_name = $voucher->fund->name;
                $sponsor_name = $voucher->fund->organization->name;
                $start_date = $voucher->fund->start_date->format('Y');
                $end_date = format_date_locale($voucher->fund->end_date, 'long_date_locale');
                $phone = $voucher->fund->organization->phone;
                $email = $voucher->fund->organization->email;
                $webshopLink = env('WEB_SHOP_GENERAL_URL');

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
        }
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

        if (($granted = $request->input('granted', null)) !== null) {
            if (!$granted) {
                $query->whereNull('identity_address');
            } else {
                $query->whereNotNull('identity_address');
            }
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
        $query = self::search($request);

        $query->whereNotNull('employee_id');
        $query->whereHas('fund', static function(Builder $query) use (
            $organization, $fund
        ) {
            $query->where('organization_id', $organization->id);

            if ($fund) {
                $query->where('id', $fund->id);
            }
        });

        if ($request->has('unassigned')) {
            if ($request->input('unassigned')) {
                $query->whereNull('identity_address');
            } else {
                $query->whereNotNull('identity_address');
            }
        }

        switch ($request->input('type', null)) {
            case 'fund_voucher': $query->whereNull('product_id'); break;
            case 'product_voucher': $query->whereNotNull('product_id'); break;
        }

        if ($request->has('q') && $q = $request->input('q')) {
            $identityRepo = resolve('forus.services.identity');

            $query->where(static function (Builder $query) use ($q, $identityRepo) {
                $query->where('note', 'LIKE', "%{$q}%");
                $query->orWhereIn(
                    'identity_address',
                    $identityRepo->identityAddressesByEmailSearch($q)
                );
            });
        }

        if ($request->has('sort_by') && $sortBy = $request->input('sort_by')) {
            $query->orderBy($sortBy, $request->input('sort_order', 'asc'));
        }

        return $query;
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
     */
    public function getIsGrantedAttribute(): bool {
        return !empty($this->identity_address);
    }

    /**
     * @return bool
     */
    public function getHasTransactionsAttribute(): bool {
        return count($this->transactions) > 0;
    }

    /**
     * Assign voucher to identity
     *
     * @param $identity_address
     * @return $this
     */
    public function assignToIdentity(string $identity_address): self {
        $this->update(compact('identity_address'));

        VoucherAssigned::dispatch($this);

        return $this;
    }

    /**
     * @param Product $product
     * @param float|null $price
     * @param bool $returnable
     * @return Voucher|\Illuminate\Database\Eloquent\Model
     */
    public function buyProductVoucher(
        Product $product,
        float $price = null,
        $returnable = true
    ) {
        $price = !$price && ($price !== 0) ? $product->price : $price;

        $voucherExpireAt = $this->fund->end_date->gt(
            $product->expire_at
        ) ? $product->expire_at : $this->fund->end_date;

        $voucher = self::create([
            'identity_address'  => auth_address(),
            'parent_id'         => $this->id,
            'fund_id'           => $this->fund_id,
            'product_id'        => $product->id,
            'amount'            => $price,
            'returnable'        => $returnable,
            'expire_at'         => $voucherExpireAt
        ]);

        VoucherCreated::dispatch($voucher);

        return $voucher;
    }

    /**
     * @param Collection $vouchers
     * @param $exportType
     * @return string
     */
    public static function zipVouchers(Collection $vouchers, $exportType): string {
        $vouchersData = [];
        $token_generator = resolve('token_generator');
        $zipPath = storage_path('vouchers-export');

        do {
            $zipFile = sprintf('%s/%s.zip', $zipPath, $token_generator->generate(64));
        } while (file_exists($zipFile));

        if (!file_exists($zipPath) && !mkdir($zipPath, 0777, true) && !is_dir($zipPath)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $zipPath));
        }

        $fp = fopen('php://temp/maxmemory:1048576', 'wb');
        $zip = new \ZipArchive();
        $zip->open($zipFile, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

        if ($exportType === 'png') {
            $zip->addEmptyDir('images');
        }

        foreach ($vouchers as $voucher) {
            $voucherData = new VoucherExportData($voucher);

            fputcsv($fp, (array) $voucherData->getName());
            $vouchersData[] = $voucherData;

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
     * @param string $address
     * @param string|null $identity_address
     * @return Voucher|Builder|\Illuminate\Database\Eloquent\Model
     */
    public static function findByAddress(string $address, ?string $identity_address = null) {
        return self::whereHas('tokens', static function(Builder $builder) use ($address) {
            $builder->where('address', '=', $address);
        })->where(static function(Builder $builder) use ($identity_address) {
            if ($identity_address) {
                $builder->where('identity_address', '=', $identity_address);
            }
        })->firstOrFail();
    }
}
