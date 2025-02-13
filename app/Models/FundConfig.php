<?php

namespace App\Models;

use App\Helpers\Markdown;
use App\Services\TranslationService\Traits\HasOnDemandTranslations;
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
 * @property int $reservation_approve_offset
 * @property int $reimbursement_approve_offset
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
 * @property bool $pre_check_excluded
 * @property string|null $pre_check_note
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
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Fund $fund
 * @property-read string $help_description_html
 * @property-read \App\Models\Implementation|null $implementation
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Services\TranslationService\Models\TranslationValue[] $translation_values
 * @property-read int|null $translation_values_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundConfig newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundConfig newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundConfig query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundConfig whereAllowBlockingVouchers($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundConfig whereAllowCustomAmounts($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundConfig whereAllowCustomAmountsValidator($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundConfig whereAllowDirectPayments($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundConfig whereAllowDirectRequests($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundConfig whereAllowFundRequests($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundConfig whereAllowGeneratorDirectPayments($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundConfig whereAllowPhysicalCards($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundConfig whereAllowPresetAmounts($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundConfig whereAllowPresetAmountsValidator($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundConfig whereAllowPrevalidations($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundConfig whereAllowReimbursements($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundConfig whereAllowReservations($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundConfig whereAllowVoucherRecords($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundConfig whereAllowVoucherTopUps($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundConfig whereAuth2faPolicy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundConfig whereAuth2faRememberIp($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundConfig whereAuth2faRestrictAuthSessions($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundConfig whereAuth2faRestrictEmails($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundConfig whereAuth2faRestrictReimbursements($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundConfig whereBackofficeCertificate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundConfig whereBackofficeCheckPartner($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundConfig whereBackofficeClientCert($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundConfig whereBackofficeClientCertKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundConfig whereBackofficeEnabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundConfig whereBackofficeFallback($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundConfig whereBackofficeIneligiblePolicy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundConfig whereBackofficeIneligibleRedirectUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundConfig whereBackofficeKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundConfig whereBackofficeUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundConfig whereBsnConfirmationApiTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundConfig whereBsnConfirmationTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundConfig whereBunqAllowedIp($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundConfig whereBunqKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundConfig whereBunqSandbox($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundConfig whereContactInfoEnabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundConfig whereContactInfoMessageCustom($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundConfig whereContactInfoMessageText($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundConfig whereContactInfoRequired($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundConfig whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundConfig whereCriteriaLabelRequirementShow($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundConfig whereCsvPrimaryKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundConfig whereCustomAmountMax($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundConfig whereCustomAmountMin($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundConfig whereEmailRequired($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundConfig whereEmployeeCanSeeProductVouchers($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundConfig whereFundId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundConfig whereGeneratorIgnoreFundBudget($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundConfig whereHashBsn($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundConfig whereHashBsnSalt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundConfig whereHashPartnerDeny($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundConfig whereHelpBlockText($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundConfig whereHelpButtonText($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundConfig whereHelpChat($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundConfig whereHelpDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundConfig whereHelpEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundConfig whereHelpEnabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundConfig whereHelpPhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundConfig whereHelpShowChat($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundConfig whereHelpShowEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundConfig whereHelpShowPhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundConfig whereHelpShowWebsite($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundConfig whereHelpTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundConfig whereHelpWebsite($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundConfig whereHideMeta($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundConfig whereIbanNameRecordKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundConfig whereIbanRecordKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundConfig whereIconnectApiOin($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundConfig whereIconnectBaseUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundConfig whereIconnectCert($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundConfig whereIconnectCertPass($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundConfig whereIconnectCertTrust($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundConfig whereIconnectEnv($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundConfig whereIconnectKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundConfig whereIconnectKeyPass($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundConfig whereIconnectTargetBinding($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundConfig whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundConfig whereImplementationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundConfig whereIsConfigured($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundConfig whereKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundConfig whereLimitGeneratorAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundConfig whereLimitVoucherTopUpAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundConfig whereLimitVoucherTotalAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundConfig whereOutcomeType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundConfig wherePreCheckExcluded($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundConfig wherePreCheckNote($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundConfig whereProviderProductsRequired($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundConfig whereRecordValidityDays($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundConfig whereRecordValidityStartDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundConfig whereReimbursementApproveOffset($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundConfig whereReservationApproveOffset($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundConfig whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundConfig whereVoucherAmountVisible($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FundConfig whereVouchersType($value)
 * @mixin \Eloquent
 */
class FundConfig extends BaseModel
{
    use HasOnDemandTranslations;

    public const string BACKOFFICE_INELIGIBLE_POLICY_REDIRECT = 'redirect';
    public const string BACKOFFICE_INELIGIBLE_POLICY_FUND_REQUEST = 'fund_request';

    public const array BACKOFFICE_INELIGIBLE_POLICIES = [
        self::BACKOFFICE_INELIGIBLE_POLICY_REDIRECT,
        self::BACKOFFICE_INELIGIBLE_POLICY_FUND_REQUEST,
    ];

    public const string VOUCHERS_TYPE_EXTERNAL = 'external';
    public const string VOUCHERS_TYPE_INTERNAL = 'internal';

    public const string AUTH_2FA_POLICY_GLOBAL = 'global';
    public const string AUTH_2FA_POLICY_OPTIONAL = 'optional';
    public const string AUTH_2FA_POLICY_REQUIRED = 'required';
    public const string AUTH_2FA_POLICY_RESTRICT = 'restrict_features';

    public const array AUTH_2FA_POLICIES = [
        self::AUTH_2FA_POLICY_GLOBAL,
        self::AUTH_2FA_POLICY_OPTIONAL,
        self::AUTH_2FA_POLICY_REQUIRED,
        self::AUTH_2FA_POLICY_RESTRICT,
    ];

    public const string OUTCOME_TYPE_PAYOUT = 'payout';
    public const string OUTCOME_TYPE_VOUCHER = 'voucher';

    public const array OUTCOME_TYPES = [
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
        'provider_products_required', 'criteria_label_requirement_show',
        'pre_check_excluded', 'pre_check_note',
        'reservation_approve_offset', 'reimbursement_approve_offset',
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
        'pre_check_excluded' => 'boolean',
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
