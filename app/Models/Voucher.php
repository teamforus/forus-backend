<?php

namespace App\Models;

use App\Events\Vouchers\VoucherAssigned;
use App\Events\Vouchers\VoucherCreated;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
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
 * @property-read string|null $created_at_locale
 * @property-read bool $expired
 * @property-read bool $is_granted
 * @property-read bool $has_transactions
 * @property-read string $type
 * @property-read string|null $updated_at_locale
 * @property-read bool $used
 * @property-read \App\Models\Voucher|null $parent
 * @property-read \App\Models\Product|null $product
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
 */
class Voucher extends Model
{
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
    public function fund() {
        return $this->belongsTo(Fund::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function parent() {
        return $this->belongsTo(Voucher::class, 'parent_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function product() {
        return $this->belongsTo(
            Product::class, 'product_id', 'id'
        )->withTrashed();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function transactions() {
        return $this->hasMany(VoucherTransaction::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function product_vouchers() {
        return $this->hasMany(Voucher::class, 'parent_id');
    }

    /**
     * @return string
     */
    public function getTypeAttribute() {
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
    public function tokens() {
        return $this->hasMany(VoucherToken::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function token_without_confirmation() {
        return $this->hasOne(VoucherToken::class)->where([
            'need_confirmation' => false
        ]);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function token_with_confirmation() {
        return $this->hasOne(VoucherToken::class)->where([
            'need_confirmation' => true
        ]);
    }

    /**
     * The voucher is expired
     *
     * @return bool
     */
    public function getExpiredAttribute() {
        return !!$this->expire_at->isPast();
    }

    /**
     * The voucher is expired
     *
     * @return bool
     */
    public function getUsedAttribute() {
        return $this->type == 'product' ? $this->transactions->count() > 0 :
            $this->amount_available_cached == 0;
    }

    /**
     * @param string|null $email
     */
    public function sendToEmail(
        string $email = null
    ) {
        /** @var VoucherToken $voucherToken */
        $voucherToken = $this->tokens()->where([
            'need_confirmation' => false
        ])->first();

        if ($voucherToken->voucher->type == 'product') {
            $fund_product_name = $voucherToken->voucher->product->name;
        } else {
            $fund_product_name = $voucherToken->voucher->fund->name;
        }

        resolve('forus.services.notification')->sendVoucher(
            $email,
            $this->identity_address,
            $fund_product_name,
            $fund_product_name,
            $voucherToken->getQrCodeUrl()
        );
    }

    /**
     * @param string $reason
     * @param bool $sendCopyToUser
     */
    public function shareVoucherEmail(string $reason, $sendCopyToUser = false) {
        $notificationService = resolve('forus.services.notification');

        /** @var VoucherToken $voucherToken */
        $voucherToken = $this->tokens()->where([
            'need_confirmation' => false
        ])->first();

        if ($voucherToken->voucher->type == 'product') {

            $recordRepo = resolve('forus.services.record');
            $primaryEmail = $recordRepo->primaryEmailByAddress(auth()->id());

            $product_name = $voucherToken->voucher->product->name;

            $notificationService->shareProductVoucher(
                $voucherToken->voucher->product->organization->email,
                $voucherToken->voucher->product->organization->emailServiceId(),
                $primaryEmail,
                $product_name,
                $voucherToken->getQrCodeUrl(),
                $reason
            );

            if ($sendCopyToUser) {
                $notificationService->shareProductVoucher(
                    $primaryEmail,
                    auth()->id(),
                    $primaryEmail,
                    $product_name,
                    $voucherToken->getQrCodeUrl(),
                    $reason
                );
            }
        }
    }

    /**
     * @return void
     */
    public function sendEmailAvailableAmount()
    {
        if (!$this->identity_address) {
            return;
        }

        $amount = $this->parent ? $this->parent->amount_available : $this->amount_available;
        $fund_name = $this->fund->name;
        $email = resolve('forus.services.record')->primaryEmailByAddress(
            $this->identity_address
        );

        resolve('forus.services.notification')->sendVoucherAmountLeftEmail(
            $email,
            $this->identity_address,
            $fund_name,
            $amount
        );
    }

    /**
     * @param int $days
     */
    public static function checkVoucherExpireQueue(int $days = 4 * 7)
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
                $end_date = $voucher->fund->end_date->format('l, d F Y');
                $phone = $voucher->fund->organization->phone;
                $email = $voucher->fund->organization->email;
                $webshopLink = env('WEB_SHOP_GENERAL_URL');

                $notificationService->voucherExpireSoon(
                    $primaryEmail,
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
    ) {
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
     * @param Fund $fund
     * @return Builder
     */
    public static function searchSponsor(
        Request $request,
        Organization $organization,
        Fund $fund = null
    ) {
        $query = self::search($request);

        $query->whereNotNull('employee_id');
        $query->whereHas('fund', function(Builder $query) use (
            $organization, $fund
        ) {
            $query->where('organization_id', $organization->id);

            if ($fund) {
                $query->where('id', $fund->id);
            }
        });

        switch ($request->input('type', null)) {
            case 'fund_voucher': $query->whereNull('product_id'); break;
            case 'product_voucher': $query->whereNotNull('product_id'); break;
        }

        if ($request->has('q') && $q = $request->input('q')) {
            $query->where('note', 'LIKE', "%{$q}%");
        }

        return $query;
    }

    /**
     * @return bool
     */
    public function getIsGrantedAttribute() {
        return !empty($this->identity_address);
    }

    /**
     * @return bool
     */
    public function getHasTransactionsAttribute() {
        return count($this->transactions) > 0;
    }

    /**
     * Assign voucher to identity
     *
     * @param $identity_address
     * @return $this
     */
    public function assignToIdentity(string $identity_address) {
        $this->update(compact('identity_address'));

        VoucherAssigned::dispatch($this);

        return $this;
    }

    /**
     * @param Organization $organization
     * @param $fromDate
     * @param $toDate
     * @return Builder[]|\Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Relations\HasManyThrough[]
     */
    public static function getUnassignedVouchers(
        Organization $organization, $fromDate, $toDate
    ) {
        $vouchers = $organization->vouchers()->whereNull(
            'identity_address'
        )->whereHas(
            'tokens', function(Builder $query) {
                return $query->where('need_confirmation', 0);
            }
        );

        if ($fromDate) {
            $vouchers->where('vouchers.created_at', '>=', $fromDate);
        }
        if ($toDate) {
            $vouchers->where('vouchers.created_at', '<=', $toDate);
        }

        return $vouchers->get();
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

        $voucher = Voucher::create([
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
     * @param Collection|Voucher[] $vouchers
     * @return string
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public static function zipVouchers(Collection $vouchers)
    {
        $token_generator = resolve('token_generator');
        $zipPath = storage_path('vouchers-export');

        do {
            $zipFile = sprintf('%s/%s.zip', $zipPath, $token_generator->generate(64));
        } while (file_exists($zipFile));

        if (!file_exists($zipPath)) {
            mkdir($zipPath, 0777, true);
        }

        $fp = fopen('php://temp/maxmemory:1048576', 'w');

        $zip = new \ZipArchive();
        $zip->open($zipFile, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        $zip->addEmptyDir('images');

        // $tmp_images = [];
        foreach ($vouchers as $voucher) {
            $name = $token_generator->generate(6, 2);
            $zip->addFromString(
                "images/$name.png",
                $voucher->token_without_confirmation->getQrCodeFile()
            );

            fputcsv($fp, [$name]);
        }

        rewind($fp);
        $zip->addFromString('qr_codes.csv', stream_get_contents($fp));
        fclose($fp);

        $zip->close();

        return $zipFile;
    }
}
