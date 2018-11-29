<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class FundConfig
 * @property mixed $id
 * @property mixed $fund_id
 * @property string $key
 * @property string $bunq_key
 * @property array $bunq_allowed_ip
 * @property bool $bunq_sandbox
 * @property string $formula_amount
 * @property string $formula_multiplier
 * @property Implementation $implementation
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @package App\Models
 */
class FundConfig extends Model
{
    protected $fillable = [];

    protected $hidden = [
        'bunq_key', 'bunq_sandbox', 'bunq_allowed_ip', 'formula_amount',
        'formula_multiplier'
    ];

    public function getBunqAllowedIpAttribute($value) {
        return collect(explode(',', $value))->filter()->toArray();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function implementation() {
        return $this->belongsTo(Implementation::class);
    }
}
