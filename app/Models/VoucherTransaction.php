<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Class VoucherTransaction
 * @property mixed $id
 * @property integer $voucher_id
 * @property integer $organization_id
 * @property integer $product_id
 * @property string $address
 * @property float $amount
 * @property integer $attempts
 * @property integer $payment_id
 * @property string $state
 * @property Product $product
 * @property Voucher $voucher
 * @property Organization $provider
 * @property Organization $organization
 * @property Collection $notes
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @package App\Models
 */
class VoucherTransaction extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'voucher_id', 'organization_id', 'product_id', 'address', 'amount',
        'state', 'payment_id', 'attempts', 'last_attempt_at'
    ];

    protected $hidden = [
        'voucher_id', 'last_attempt_at', 'attempts', 'notes'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function product() {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function provider() {
        return $this->belongsTo(Organization::class, 'organization_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function voucher() {
        return $this->belongsTo(Voucher::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function notes() {
        return $this->hasMany(VoucherTransactionNote::class);
    }

    /**
     * @return mixed
     */
    public function getTransactionDetailsAttribute()
    {
        return collect($this->voucher->fund->getBunq()->paymentDetails(
            $this->payment_id
        ));
    }

    /**
     * @param Request $request
     * @return Builder
     */
    public static function search(
        Request $request
    ) {
        $query = self::query();

        if ($request->has('q') && $q = $request->input('q', '')) {
            $query->where(function (Builder $query) use ($q) {
                $query->whereHas('provider', function (Builder $query) use ($q) {
                    $query->where('name', 'LIKE', "%{$q}%");
                });

                $query->orWhereHas('voucher.fund', function (Builder $query) use ($q) {
                    $query->where('name', 'LIKE', "%{$q}%");
                });
            });
        }

        if ($request->has('state') && $state = $request->input('state')) {
            $query->where('state', $state);
        }

        if ($request->has('from') && $from = $request->input('from')) {
            $query->where(
                'created_at',
                '>=',
                (new Carbon($from))->startOfDay()->format('Y-m-d H:i:s')
            );
        }

        if ($request->has('to') && $to = $request->input('to')) {
            $query->where(
                'created_at',
                '<=',
                (new Carbon($to))->endOfDay()->format('Y-m-d H:i:s')
            );
        }

        if ($amount_min = $request->input('amount_min')) {
            $query->where('amount', '>=', $amount_min);
        }

        if ($amount_max = $request->input('amount_max')) {
            $query->where('amount', '<=', $amount_max);
        }

        $query = $query->latest();

        return $query;
    }

    /**
     * @param Organization $organization
     * @param Request $request
     * @return Builder
     */
    public static function searchSponsor(
        Organization $organization,
        Request $request
    ) {
        return self::search($request)->whereHas('voucher.fund.organization', function (Builder $query) use ($organization) {
            $query->where('id', $organization->id);
        });
    }

    /**
     * @param Organization $organization
     * @param Request $request
     * @return Builder
     */
    public static function searchProvider(
        Organization $organization,
        Request $request
    ) {
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
    ) {
        return self::search($request)->where([
            'voucher_id' => $voucher->id
        ]);
    }
}
