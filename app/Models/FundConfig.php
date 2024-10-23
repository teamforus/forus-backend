<?php

namespace App\Models;

use App\Helpers\Markdown;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use League\CommonMark\Exception\CommonMarkException;

/**
 * App\Models\FundConfig
 *
 * @property int $id
 * @property int $fund_id
 * @property int|null $implementation_id
 * @property string $key
 * @property string $outcome_type
 * @property string|null $iban_record_key
 * @property string|null $iban_name_record_key
 * @property bool $hide_meta
 * @property bool $voucher_amount_visible
 * @property string|null $auth_2fa_policy
 * @property bool $auth_2fa_remember_ip
 * @property bool $auth_2fa_restrict_emails
 * @property bool $auth_2fa_restrict_auth_sessions
 * @property bool $auth_2fa_restrict_reimbursements
 * @property int|null $record_validity_days
 * @property \Illuminate\Support\Carbon|null $record_validity_start_date
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
 * @property bool $allow_reservations
 * @property bool $allow_reimbursements
 * @property bool $allow_direct_payments
 * @property bool $allow_generator_direct_payments
 * @property bool $allow_voucher_top_ups
 * @property bool $allow_voucher_records
 * @property bool $allow_custom_amounts
 * @property bool $allow_custom_amounts_validator
 * @property bool $allow_preset_amounts
 * @property bool $allow_preset_amounts_validator
 * @property string|null $custom_amount_min
 * @property string|null $custom_amount_max
 * @property bool $employee_can_see_product_vouchers
 * @property string $vouchers_type
 * @property bool $is_configured
 * @property bool $email_required
 * @property bool $contact_info_enabled
 * @property bool $contact_info_required
 * @property bool $contact_info_message_custom
 * @property string|null $contact_info_message_text
 * @property string|null $limit_generator_amount
 * @property string|null $limit_voucher_top_up_amount
 * @property string|null $limit_voucher_total_amount
 * @property bool $generator_ignore_fund_budget
 * @property int|null $bsn_confirmation_time
 * @property int|null $bsn_confirmation_api_time
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
 * @property string $iconnect_env
 * @property string $iconnect_key
 * @property string $iconnect_key_pass
 * @property string $iconnect_cert
 * @property string $iconnect_cert_pass
 * @property string $iconnect_cert_trust
 * @property bool $provider_products_required
 * @property bool $help_enabled
 * @property string|null $help_title
 * @property string|null $help_block_text
 * @property string|null $help_button_text
 * @property string|null $help_email
 * @property string|null $help_phone
 * @property string|null $help_website
 * @property string|null $help_chat
 * @property string|null $help_description
 * @property bool $help_show_email
 * @property bool $help_show_phone
 * @property bool $help_show_website
 * @property bool $help_show_chat
 * @property string $criteria_label_requirement_show
 * @property int $pre_check_excluded
 * @property string $shown_criteria_label_details
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Fund $fund
 * @property-read string $help_description_html
 * @property-read \App\Models\Implementation|null $implementation
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig query()
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig whereAllowBlockingVouchers($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig whereAllowCustomAmounts($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig whereAllowCustomAmountsValidator($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig whereAllowDirectPayments($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig whereAllowDirectRequests($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig whereAllowFundRequests($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig whereAllowGeneratorDirectPayments($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig whereAllowPhysicalCards($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig whereAllowPresetAmounts($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig whereAllowPresetAmountsValidator($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig whereAllowPrevalidations($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig whereAllowReimbursements($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig whereAllowReservations($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig whereAllowVoucherRecords($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig whereAllowVoucherTopUps($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig whereAuth2faPolicy($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig whereAuth2faRememberIp($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig whereAuth2faRestrictAuthSessions($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig whereAuth2faRestrictEmails($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig whereAuth2faRestrictReimbursements($value)
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
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig whereCriteriaLabelRequirementShow($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig whereCsvPrimaryKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig whereCustomAmountMax($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig whereCustomAmountMin($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig whereEmailRequired($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig whereEmployeeCanSeeProductVouchers($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig whereFundId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig whereGeneratorIgnoreFundBudget($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig whereHashBsn($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig whereHashBsnSalt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig whereHashPartnerDeny($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig whereHelpBlockText($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig whereHelpButtonText($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig whereHelpChat($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig whereHelpDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig whereHelpEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig whereHelpEnabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig whereHelpPhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig whereHelpShowChat($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig whereHelpShowEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig whereHelpShowPhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig whereHelpShowWebsite($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig whereHelpTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig whereHelpWebsite($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig whereHideMeta($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig whereIbanNameRecordKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig whereIbanRecordKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig whereIconnectApiOin($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig whereIconnectBaseUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig whereIconnectCert($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig whereIconnectCertPass($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig whereIconnectCertTrust($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig whereIconnectEnv($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig whereIconnectKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig whereIconnectKeyPass($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig whereIconnectTargetBinding($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig whereImplementationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig whereIsConfigured($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig whereKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig whereLimitGeneratorAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig whereLimitVoucherTopUpAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig whereLimitVoucherTotalAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig whereOutcomeType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig wherePreCheckExcluded($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig whereProviderProductsRequired($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig whereRecordValidityDays($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig whereRecordValidityStartDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig whereShownCriteriaLabelDetails($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig whereVoucherAmountVisible($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundConfig whereVouchersType($value)
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

    public const VOUCHERS_TYPE_EXTERNAL = 'external';
    public const VOUCHERS_TYPE_INTERNAL = 'internal';

    public const AUTH_2FA_POLICY_GLOBAL = 'global';
    public const AUTH_2FA_POLICY_OPTIONAL = 'optional';
    public const AUTH_2FA_POLICY_REQUIRED = 'required';
    public const AUTH_2FA_POLICY_RESTRICT = 'restrict_features';

    public const AUTH_2FA_POLICIES = [
        self::AUTH_2FA_POLICY_GLOBAL,
        self::AUTH_2FA_POLICY_OPTIONAL,
        self::AUTH_2FA_POLICY_REQUIRED,
        self::AUTH_2FA_POLICY_RESTRICT,
    ];

    public const OUTCOME_TYPE_PAYOUT = 'payout';
    public const OUTCOME_TYPE_VOUCHER = 'voucher';

    public const OUTCOME_TYPES = [
        self::OUTCOME_TYPE_PAYOUT,
        self::OUTCOME_TYPE_VOUCHER,
    ];

    protected $fillable = [
        'backoffice_enabled', 'backoffice_url', 'backoffice_key',
        'backoffice_certificate', 'backoffice_fallback',
        'backoffice_ineligible_policy', 'backoffice_ineligible_redirect_url',
        'email_required', 'contact_info_enabled', 'contact_info_required',
        'contact_info_message_custom', 'contact_info_message_text',
        'auth_2fa_policy', 'auth_2fa_remember_ip', 'hide_meta', 'voucher_amount_visible',
        'allow_custom_amounts_validator', 'allow_preset_amounts_validator',
        'allow_custom_amounts', 'allow_preset_amounts', 'custom_amount_min', 'custom_amount_max',
        'help_enabled', 'help_title', 'help_block_text', 'help_show_chat',
        'help_button_text', 'help_email', 'help_phone', 'help_website', 'help_chat',
        'help_description', 'help_show_email', 'help_show_phone', 'help_show_website',
        'provider_products_required', 'criteria_label_requirement_show', 'pre_check_excluded',
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
        'iconnect_env', 'iconnect_key', 'iconnect_key_pass',
        'iconnect_cert', 'iconnect_cert_pass', 'iconnect_cert_trust',
        'allow_direct_payments', 'allow_voucher_top_ups', 'allow_voucher_records',
        'limit_voucher_top_up_amount', 'limit_voucher_total_amount', 'allow_generator_direct_payments',
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
        'allow_blocking_vouchers' => 'boolean',
        'allow_direct_payments' => 'boolean',
        'allow_voucher_top_ups' => 'boolean',
        'allow_voucher_records' => 'boolean',
        'backoffice_check_partner' => 'boolean',
        'record_validity_start_date' => 'date',
        'employee_can_see_product_vouchers' => 'boolean',
        'email_required' => 'boolean',
        'contact_info_enabled' => 'boolean',
        'contact_info_required' => 'boolean',
        'contact_info_message_custom' => 'boolean',
        'allow_reservations' => 'boolean',
        'allow_reimbursements' => 'boolean',
        'limit_generator_amount' => 'string',
        'limit_voucher_top_up_amount' => 'string',
        'limit_voucher_total_amount' => 'string',
        'allow_generator_direct_payments' => 'boolean',
        'generator_ignore_fund_budget' => 'boolean',
        'auth_2fa_remember_ip' => 'boolean',
        'auth_2fa_restrict_emails' => 'boolean',
        'auth_2fa_restrict_auth_sessions' => 'boolean',
        'auth_2fa_restrict_reimbursements' => 'boolean',
        'hide_meta' => 'boolean',
        'voucher_amount_visible' => 'boolean',
        'allow_custom_amounts' => 'boolean',
        'allow_preset_amounts' => 'boolean',
        'allow_custom_amounts_validator' => 'boolean',
        'allow_preset_amounts_validator' => 'boolean',
        'provider_products_required' => 'boolean',
        'help_enabled' => 'boolean',
        'help_show_email' => 'boolean',
        'help_show_phone' => 'boolean',
        'help_show_website' => 'boolean',
        'help_show_chat' => 'boolean',
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

    /**
     * @return bool
     */
    public function usesExternalVouchers(): bool
    {
        return $this->vouchers_type == self::VOUCHERS_TYPE_EXTERNAL;
    }

    /**
     * @return bool
     */
    public function isPayoutOutcome(): bool
    {
        return $this->outcome_type === self::OUTCOME_TYPE_PAYOUT;
    }

    /**
     * @return string
     * @noinspection PhpUnused
     * @throws CommonMarkException
     */
    public function getHelpDescriptionHtmlAttribute(): string
    {
        return Markdown::convert($this->help_description ?: '');
    }
}
