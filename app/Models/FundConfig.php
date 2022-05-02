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
 * @property bool $allow_physical_cards
 * @property bool $allow_fund_requests
 * @property bool $allow_prevalidations
 * @property bool $allow_direct_requests
 * @property bool $allow_blocking_vouchers
 * @property bool $employee_can_see_product_vouchers
 * @property bool $is_configured
 * @property bool $limit_generator_amount
 * @property bool $backoffice_enabled
 * @property bool $backoffice_status
 * @property string|null $backoffice_url
 * @property string|null $backoffice_key
 * @property string|null $backoffice_certificate
 * @property bool $backoffice_fallback
 * @property string|null $backoffice_ineligible_policy
 * @property string|null $backoffice_ineligible_redirect_url
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Fund $fund
 * @property-read \App\Models\Implementation|null $implementation
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig query()
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig whereAllowBlockingVouchers($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig whereAllowDirectRequests($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig whereAllowFundRequests($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig whereAllowPhysicalCards($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig whereAllowPrevalidations($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig whereBackofficeCertificate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig whereBackofficeEnabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig whereBackofficeFallback($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig whereBackofficeIneligiblePolicy($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig whereBackofficeIneligibleRedirectUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig whereBackofficeKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig whereBackofficeStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig whereBackofficeUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig whereBunqAllowedIp($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig whereBunqKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig whereBunqSandbox($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig whereCsvPrimaryKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig whereEmployeeCanSeeProductVouchers($value)
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
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class FundConfig extends Model
{
    public const BACKOFFICE_INELIGIBLE_POLICY_REDIRECT = 'redirect';
    public const BACKOFFICE_INELIGIBLE_POLICY_FUND_REQUEST = 'fund_request';

    public const BACKOFFICE_INELIGIBLE_POLICIES = [
        self::BACKOFFICE_INELIGIBLE_POLICY_REDIRECT,
        self::BACKOFFICE_INELIGIBLE_POLICY_FUND_REQUEST,
    ];

    protected $fillable = [
        'backoffice_enabled', 'backoffice_url', 'backoffice_key',
        'backoffice_certificate', 'backoffice_fallback',
        'backoffice_ineligible_policy', 'backoffice_ineligible_redirect_url',
    ];

    /**
     * @var string[]
     */
    protected $hidden = [
        'bunq_key', 'bunq_sandbox', 'bunq_allowed_ip', 'formula_amount',
        'formula_multiplier', 'is_configured', 'allow_physical_cards',
        'csv_primary_key', 'subtract_transaction_costs',
        'implementation_id', 'implementation', 'hash_partner_deny', 'limit_generator_amount',
        'backoffice_enabled', 'backoffice_status', 'backoffice_url', 'backoffice_key',
        'backoffice_certificate', 'backoffice_fallback',
        'backoffice_ineligible_policy', 'backoffice_ineligible_redirect_url',
        'allow_fund_requests', 'allow_prevalidations',
    ];

    /**
     * @var array
     */
    protected $casts = [
        'hash_bsn' => 'boolean',
        'is_configured' => 'boolean',
        'hash_partner_deny' => 'boolean',
        'backoffice_status' => 'boolean',
        'backoffice_enabled' => 'boolean',
        'backoffice_fallback' => 'boolean',
        'allow_fund_requests' => 'boolean',
        'allow_prevalidations' => 'boolean',
        'allow_physical_cards' => 'boolean',
        'allow_direct_requests' => 'boolean',
        'limit_generator_amount' => 'boolean',
        'allow_blocking_vouchers' => 'boolean',
        'employee_can_see_product_vouchers' => 'boolean',
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

    /**
     * @return bool
     */
    public function shouldRedirectOnIneligibility(): bool
    {
        return $this->backoffice_ineligible_policy == self::BACKOFFICE_INELIGIBLE_POLICY_REDIRECT;
    }
}
