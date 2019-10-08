<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\BunqMeTab
 *
 * @property int $id
 * @property int $bunq_me_tab_id
 * @property int $monetary_account_id
 * @property int $fund_id
 * @property string $status
 * @property float $amount
 * @property string $description
 * @property string $uuid
 * @property string $share_url
 * @property string|null $issuer_authentication_url
 * @property \Illuminate\Support\Carbon|null $last_check_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Fund $fund
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\BunqMeTab newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\BunqMeTab newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\BunqMeTab query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\BunqMeTab whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\BunqMeTab whereBunqMeTabId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\BunqMeTab whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\BunqMeTab whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\BunqMeTab whereFundId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\BunqMeTab whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\BunqMeTab whereIssuerAuthenticationUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\BunqMeTab whereLastCheckAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\BunqMeTab whereMonetaryAccountId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\BunqMeTab whereShareUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\BunqMeTab whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\BunqMeTab whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\BunqMeTab whereUuid($value)
 * @mixin \Eloquent
 */
class BunqMeTab extends Model
{
    const STATUS_PAID = 'PAID';
    const STATUS_EXPIRED = 'EXPIRED';
    const STATUS_CANCELED = 'CANCELED';
    const STATUS_WAITING_FOR_PAYMENT = 'WAITING_FOR_PAYMENT';

    const STATUSES = [
        self::STATUS_PAID,
        self::STATUS_EXPIRED,
        self::STATUS_CANCELED,
        self::STATUS_WAITING_FOR_PAYMENT,
    ];

    /**
     * @var array
     */
    protected $fillable = [
        'fund_id', 'bunq_me_tab_id', 'monetary_account_id', 'status',
        'last_check_at', 'amount', 'description', 'uuid', 'share_url',
        'issuer_authentication_url',
    ];

    /**
     * @var array
     */
    protected $dates = [
        'last_check_at', 'created_at', 'updated_at',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function fund() {
        return $this->belongsTo(Fund::class);
    }
}
