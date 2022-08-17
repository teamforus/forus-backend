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
 * @property bool $show_voucher_qr
 * @property bool $show_voucher_amount
 * @property bool $is_configured
 * @property bool $email_required
 * @property bool $contact_info_enabled
 * @property bool $contact_info_required
 * @property bool $contact_info_message_custom
 * @property string|null $contact_info_message_text
 * @property bool $limit_generator_amount
 * @property int|null $bsn_confirmation_time
 * @property int $bsn_confirmation_api_time
 * @property bool $backoffice_enabled
 * @property bool $backoffice_check_partner
 * @property string|null $backoffice_url
 * @property string|null $backoffice_key
 * @property string|null $backoffice_certificate
 * @property string $backoffice_client_cert
 * @property string $backoffice_client_cert_key
 * @property bool $backoffice_fallback
 * @property string|null $backoffice_ineligible_policy
 * @property string|null $backoffice_ineligible_redirect_url
 * @property string|null $iconnect_target_binding
 * @property string|null $iconnect_api_oin
 * @property string|null $iconnect_base_url
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
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig whereBackofficeCheckPartner($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig whereBackofficeClientCert($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig whereBackofficeClientCertKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig whereBackofficeEnabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig whereBackofficeFallback($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig whereBackofficeIneligiblePolicy($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig whereBackofficeIneligibleRedirectUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig whereBackofficeKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig whereBackofficeUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig whereBsnConfirmationApiTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig whereBsnConfirmationTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig whereBunqAllowedIp($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig whereBunqKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig whereBunqSandbox($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig whereContactInfoEnabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig whereContactInfoMessageCustom($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig whereContactInfoMessageText($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig whereContactInfoRequired($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig whereCsvPrimaryKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig whereEmailRequired($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig whereEmployeeCanSeeProductVouchers($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig whereFundId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig whereHashBsn($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig whereHashBsnSalt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig whereHashPartnerDeny($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig whereIconnectApiOin($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig whereIconnectBaseUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig whereIconnectTargetBinding($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig whereImplementationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig whereIsConfigured($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig whereKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig whereLimitGeneratorAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig whereRecordValidityDays($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class FundConfig extends BaseModel
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
        'email_required', 'contact_info_enabled', 'contact_info_required',
        'contact_info_message_custom', 'contact_info_message_text',
    ];

    /**
     * @var string[]
     */
    protected $hidden = [
        'bunq_key', 'bunq_sandbox', 'bunq_allowed_ip', 'formula_amount',
        'formula_multiplier', 'is_configured', 'allow_physical_cards',
        'csv_primary_key', 'subtract_transaction_costs',
        'implementation_id', 'implementation', 'hash_partner_deny', 'limit_generator_amount',
        'backoffice_enabled', 'backoffice_url', 'backoffice_key', 'backoffice_check_partner',
        'backoffice_certificate', 'backoffice_fallback',
        'backoffice_client_cert', 'backoffice_client_cert_key',
        'backoffice_ineligible_policy', 'backoffice_ineligible_redirect_url',
        'allow_fund_requests', 'allow_prevalidations',
        'iconnect_target_binding', 'iconnect_api_oin', 'iconnect_base_url',
    ];

    /**
     * @var array
     */
    protected $casts = [
        'hash_bsn' => 'boolean',
        'is_configured' => 'boolean',
        'hash_partner_deny' => 'boolean',
        'backoffice_enabled' => 'boolean',
        'backoffice_fallback' => 'boolean',
        'allow_fund_requests' => 'boolean',
        'allow_prevalidations' => 'boolean',
        'allow_physical_cards' => 'boolean',
        'allow_direct_requests' => 'boolean',
        'limit_generator_amount' => 'boolean',
        'allow_blocking_vouchers' => 'boolean',
        'backoffice_check_partner' => 'boolean',
        'employee_can_see_product_vouchers' => 'boolean',
        'email_required' => 'boolean',
        'contact_info_enabled' => 'boolean',
        'contact_info_required' => 'boolean',
        'contact_info_message_custom' => 'boolean',
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

    /**
     * @return string
     */
    public function getDefaultContactInfoMessage(): string
    {
        return trans('fund.default_contact_info_message');
    }
}
