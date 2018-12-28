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
 * @property boolean $subtract_transaction_costs
 * @property Implementation $implementation
 * @property boolean $is_configured
 * @property string $csv_primary_key
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @package App\Models
 */
class FundConfig extends Model
{
    protected $fillable = [];

    protected $hidden = [
        'bunq_key', 'bunq_sandbox', 'bunq_allowed_ip', 'formula_amount',
        'formula_multiplier', 'is_configured', 'csv_primary_key', 
        'subtract_transaction_costs'
    ];

    /**
     * @var array
     */
    protected $casts = [
        'is_configured' => 'boolean'
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
