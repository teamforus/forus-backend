<?php

namespace App\Models;

/**
 * App\Models\BunqMeTab
 *
 * @property int $id
 * @property int $bunq_me_tab_id
 * @property int $monetary_account_id
 * @property int $fund_id
 * @property string $status
 * @property string $amount
 * @property string $description
 * @property string $uuid
 * @property string $share_url
 * @property string|null $issuer_authentication_url
 * @property \Illuminate\Support\Carbon|null $last_check_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Fund $fund
 * @method static \Illuminate\Database\Eloquent\Builder|BunqMeTab newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|BunqMeTab newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|BunqMeTab query()
 * @method static \Illuminate\Database\Eloquent\Builder|BunqMeTab whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BunqMeTab whereBunqMeTabId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BunqMeTab whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BunqMeTab whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BunqMeTab whereFundId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BunqMeTab whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BunqMeTab whereIssuerAuthenticationUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BunqMeTab whereLastCheckAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BunqMeTab whereMonetaryAccountId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BunqMeTab whereShareUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BunqMeTab whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BunqMeTab whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BunqMeTab whereUuid($value)
 * @mixin \Eloquent
 */
class BunqMeTab extends Model
{
    public const STATUS_PAID = 'PAID';
    public const STATUS_EXPIRED = 'EXPIRED';
    public const STATUS_CANCELED = 'CANCELED';
    public const STATUS_WAITING_FOR_PAYMENT = 'WAITING_FOR_PAYMENT';

    public const STATUSES = [
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
