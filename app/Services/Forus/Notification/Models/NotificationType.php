<?php

namespace App\Services\Forus\Notification\Models;

use App\Mail\Auth\UserLoginMail;
use App\Mail\Funds\FundBalanceWarningMail;
use App\Mail\Funds\FundCreatedMail;
use App\Mail\Funds\FundExpiredMail;
use App\Mail\Funds\FundStartedMail;
use App\Mail\Funds\NewFundApplicableMail;
use App\Mail\Funds\ProductAddedMail;
use App\Mail\Funds\ProviderAppliedMail;
use App\Mail\Funds\ProviderApprovedMail;
use App\Mail\Funds\ProviderRejectedMail;
use App\Mail\User\EmailActivationMail;
use App\Mail\Validations\AddedAsValidatorMail;
use App\Mail\Validations\NewValidationRequestMail;
use App\Mail\Vouchers\PaymentSuccessMail;
use App\Mail\Vouchers\ProductReservedMail;
use App\Mail\Vouchers\ProductSoldOutMail;
use App\Mail\Vouchers\SendVoucherMail;
use App\Mail\Vouchers\ShareProductVoucherMail;
use App\Models\Model;
use App\Models\NotificationPreference;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class NotificationType
 * @property int $id
 * @property string $key
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property NotificationPreference[]|Collection $notification_preferences
 * @package App\Services\Forus\Notification\Models
 */
class NotificationType extends Model
{
    /**
     * @var array
     */
    protected $fillable = [
        'key'
    ];

    /**
     * Map between type keys and Mail classes
     * @var array
     */
    protected static $mailMap = [
        // User generated emails
        'vouchers.send_voucher' => SendVoucherMail::class,
        'vouchers.share_voucher' => ShareProductVoucherMail::class,
        'vouchers.payment_success' => PaymentSuccessMail::class,

        // Validators
        'validations.new_validation_request' => NewValidationRequestMail::class,
        'validations.you_added_as_validator' => AddedAsValidatorMail::class,

        // Mails for sponsors/providers
        'funds.new_fund_started' => FundStartedMail::class,
        'funds.new_fund_created' => FundCreatedMail::class,
        'funds.new_fund_applicable' => NewFundApplicableMail::class,
        'funds.provider_applied' => ProviderAppliedMail::class,
        'funds.provider_approved' => ProviderApprovedMail::class,
        'funds.provider_rejected' => ProviderRejectedMail::class,
        'funds.product_sold_out' => ProductSoldOutMail::class,
        'funds.product_reserved' => ProductReservedMail::class,
        'funds.fund_expires' => FundExpiredMail::class,
        'funds.product_added' => ProductAddedMail::class,
        'funds.balance_warning' => FundBalanceWarningMail::class,

        // Authorization emails
        'auth.user_login' => UserLoginMail::class,
        'auth.email_activation' => EmailActivationMail::class,
    ];

    /**
     * Emails that you can't unsubscribe from
     * @var array
     */
    protected static $mandatoryEmail = [
        'auth.user_login', 'auth.email_activation', 'vouchers.share_voucher',
    ];

    /**
     * @return array
     */
    public static function getMailMap(): array
    {
        return self::$mailMap;
    }

    /**
     * @return array
     */
    public static function getMandatoryMailKeys(): array
    {
        return self::$mandatoryEmail;
    }

    /**
     * @return HasMany
     */
    public function notification_preferences(): HasMany
    {
        return $this->hasMany(NotificationPreference::class);
    }
}