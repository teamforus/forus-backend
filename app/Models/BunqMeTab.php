<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class BunqMeTab
 * @property int $id
 * @property int $fund_id
 * @property int $bunq_me_tab_id
 * @property int $monetary_account_id
 * @property float $amount
 * @property string $status
 * @property string $uuid
 * @property string $share_url
 * @property string|null $issuer_authentication_url
 * @property Fund $fund
 * @property Carbon $last_check_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @package App\Models
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
