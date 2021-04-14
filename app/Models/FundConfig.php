<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Models\FundConfig
 *
 * @property int $id
 * @property int $fund_id
 * @property int|null $implementation_id
 * @property string $key
 * @property int|null $record_validity_days
 * @property bool $hash_bsn
 * @property string|null $hash_bsn_salt
 * @property bool $hash_partner_deny
 * @property string $bunq_key
 * @property array $bunq_allowed_ip
 * @property int $bunq_sandbox
 * @property string|null $csv_primary_key
 * @property int $subtract_transaction_costs
 * @property bool $allow_physical_cards
 * @property bool $allow_fund_requests
 * @property bool $allow_prevalidations
 * @property bool $allow_direct_requests
 * @property bool $is_configured
 * @property bool $limit_generator_amount
 * @property string|null $sponsor_api_token
 * @property string|null $sponsor_api_url
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Fund $fund
 * @property-read \App\Models\Implementation|null $implementation
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig query()
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig whereAllowDirectRequests($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig whereAllowFundRequests($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig whereAllowPhysicalCards($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig whereAllowPrevalidations($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig whereBunqAllowedIp($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig whereBunqKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig whereBunqSandbox($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig whereCsvPrimaryKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig whereFundId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig whereHashBsn($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig whereHashBsnSalt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig whereHashPartnerDeny($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig whereImplementationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig whereIsConfigured($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig whereKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig whereLimitGeneratorAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig whereRecordValidityDays($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig whereSponsorApiToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig whereSponsorApiUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig whereSubtractTransactionCosts($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class FundConfig extends Model
{
    /**
     * @var string[]
     */
    protected $hidden = [
        'bunq_key', 'bunq_sandbox', 'bunq_allowed_ip', 'formula_amount',
        'formula_multiplier', 'is_configured', 'allow_physical_cards',
        'csv_primary_key', 'subtract_transaction_costs',
        'implementation_id', 'implementation', 'hash_partner_deny',
        'limit_generator_amount',
    ];

    /**
     * @var array
     */
    protected $casts = [
        'hash_bsn' => 'boolean',
        'is_configured' => 'boolean',
        'hash_partner_deny' => 'boolean',
        'allow_fund_requests' => 'boolean',
        'allow_prevalidations' => 'boolean',
        'allow_physical_cards' => 'boolean',
        'allow_direct_requests' => 'boolean',
        'limit_generator_amount' => 'boolean',
    ];

    /**
     * @param $value
     * @return array
     * @noinspection PhpUnused
     */
    public function getBunqAllowedIpAttribute($value): array
    {
        return collect(explode(',', $value))->filter()->toArray();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     * @noinspection PhpUnused
     */
    public function implementation(): BelongsTo
    {
        return $this->belongsTo(Implementation::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     * @noinspection PhpUnused
     */
    public function fund(): BelongsTo
    {
        return $this->belongsTo(Fund::class);
    }
}
