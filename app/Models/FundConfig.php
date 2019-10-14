<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\FundConfig
 *
 * @property int $id
 * @property int $fund_id
 * @property int|null $implementation_id
 * @property string $key
 * @property string $bunq_key
 * @property string $bunq_allowed_ip
 * @property int $bunq_sandbox
 * @property string|null $csv_primary_key
 * @property int $subtract_transaction_costs
 * @property bool $is_configured
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Implementation|null $implementation
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundConfig newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundConfig newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundConfig query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundConfig whereBunqAllowedIp($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundConfig whereBunqKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundConfig whereBunqSandbox($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundConfig whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundConfig whereCsvPrimaryKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundConfig whereFundId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundConfig whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundConfig whereImplementationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundConfig whereIsConfigured($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundConfig whereKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundConfig whereSubtractTransactionCosts($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\FundConfig whereUpdatedAt($value)
 * @mixin \Eloquent
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
