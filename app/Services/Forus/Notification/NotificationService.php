<?php

namespace App\Services\Forus\Notification;

use App\Mail\Auth\UserLoginMail;
use App\Mail\FundRequests\FundRequestCreatedMail;
use App\Mail\FundRequests\FundRequestClarificationRequestedMail;
use App\Mail\FundRequests\FundRequestRecordDeclinedMail;
use App\Mail\FundRequests\FundRequestResolvedMail;
use App\Mail\Funds\FundBalanceWarningMail;
use App\Mail\Funds\FundClosed;
use App\Mail\Funds\FundClosedProvider;
use App\Mail\Funds\FundCreatedMail;
use App\Mail\Funds\FundExpiredMail;
use App\Mail\Funds\FundStartedMail;
use App\Mail\Funds\NewFundApplicableMail;
use App\Mail\Funds\ProductAddedMail;
use App\Mail\Funds\ProviderAppliedMail;
use App\Mail\Funds\ProviderApprovedMail;
use App\Mail\Funds\ProviderInvitedMail;
use App\Mail\Funds\ProviderRejectedMail;
use App\Mail\Funds\Forus\ForusFundCreated;
use App\Mail\User\EmailActivationMail;
use App\Mail\User\EmployeeAddedMail;
use App\Mail\Validations\AddedAsValidatorMail;
use App\Mail\Validations\NewValidationRequestMail;
use App\Mail\Vouchers\AssignedVoucherMail;
use App\Mail\Vouchers\FundStatisticsMail;
use App\Mail\Vouchers\PaymentSuccessMail;
use App\Mail\Vouchers\ProductReservedMail;
use App\Mail\Vouchers\ProductReservedUserMail;
use App\Mail\Vouchers\ProductSoldOutMail;
use App\Mail\Vouchers\ShareProductVoucherMail;
use App\Mail\Vouchers\SendVoucherMail;
use App\Models\Implementation;
use App\Services\ApiRequestService\ApiRequest;
use App\Services\Forus\Notification\Interfaces\INotificationRepo;
use App\Services\Forus\Notification\Models\NotificationToken;
use App\Services\Forus\Record\Repositories\Interfaces\IRecordRepo;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Notification;

/**
 * Class MailService
 * @package App\Services\Forus\MailNotification
 */
class NotificationService
{
    const TYPE_PUSH_IOS = NotificationToken::TYPE_PUSH_IOS;
    const TYPE_PUSH_ANDROID = NotificationToken::TYPE_PUSH_ANDROID;

    const TYPES = [
        self::TYPE_PUSH_IOS,
        self::TYPE_PUSH_ANDROID,
    ];

    protected $notificationRepo;
    protected $recordRepo;
    protected $apiRequest;
    protected $mailer;

    /**
     * NotificationService constructor.
     *
     * @param Mailer $mailer
     * @param ApiRequest $apiRequest
     * @param IRecordRepo $recordRepo
     * @param INotificationRepo $notificationRepo
     */
    public function __construct(
        Mailer $mailer,
        ApiRequest $apiRequest,
        IRecordRepo $recordRepo,
        INotificationRepo $notificationRepo
    ) {
        $this->mailer = $mailer;
        $this->apiRequest = $apiRequest;
        $this->recordRepo = $recordRepo;
        $this->notificationRepo = $notificationRepo;
    }

    /**
     * Add notification token for identity
     *
     * @param $identity_address
     * @param string $type
     * @param string $token
     * @return bool
     */
    public function storeNotificationToken(
        $identity_address,
        string $type,
        string $token
    ) {
        if (!in_array($type, self::TYPES)) {
            return false;
        }

        NotificationToken::firstOrCreate(
            compact('identity_address', 'type', 'token')
        );

        return true;
    }

    /**
     * Remove notification token
     *
     * @param string $token
     * @param string|null $type
     * @param null $identity_address
     */
    public function removeNotificationToken(
        string $token,
        string $type = null,
        $identity_address = null
    ) {
        $query = NotificationToken::where(compact('token'));

        if ($type) {
            $query->where(compact('type'));
        }

        if ($identity_address) {
            $query->where(compact('identity_address'));
        }

        $query->delete();
    }

    /**
     * Send push notification
     *
     * @param $identity_address
     * @param string $title
     * @param string $body
     * @param string $key
     * @return bool
     */
    public function sendPushNotification(
        $identity_address,
        string $title,
        string $body,
        string $key = null
    ) {
        if ($this->isPushUnsubscribable($key) &&
            $this->isPushUnsubscribed($identity_address, $key)) {
            return false;
        }

        /** @var NotificationToken[] $notificationTokens */
        $notificationTokens = NotificationToken::where([
            'identity_address' => $identity_address
        ])->get();

        foreach ($notificationTokens as $notificationToken) {
            if (!config(sprintf(
                'broadcasting.connections.%s',
                $notificationToken->type
            ))) {
                continue;
            }

            $notification = $notificationToken->makeBasicNotification(
                $title, $body
            );

            if ($notification) {
                Notification::route(
                    $notificationToken->type,
                    $notificationToken->token
                )->notify($notification);
            }
        }

        return true;
    }

    /**
     * Notify sponsor that new provider applied to his fund
     *
     * @param string $email
     * @param $identifier
     * @param string $provider_name
     * @param string $sponsor_name
     * @param string $fund_name
     * @param string $sponsor_dashboard_link
     * @return bool
     * @throws \Exception
     */
    public function providerApplied(
        string $email,
        $identifier,
        string $provider_name,
        string $sponsor_name,
        string $fund_name,
        string $sponsor_dashboard_link
    ) {
        return $this->sendMail($email, new ProviderAppliedMail(
            $provider_name,
            $sponsor_name,
            $fund_name,
            $sponsor_dashboard_link,
            $identifier
        ));
    }

    /**
     * Invite provider to new fund
     *
     * @param string $email
     * @param string $provider_name
     * @param string $sponsor_name
     * @param string|null $sponsor_phone
     * @param string|null $sponsor_email
     * @param string $fund_name
     * @param string $fund_start_date
     * @param string $fund_end_date
     * @param string $from_fund_name
     * @param string $invitation_link
     * @return bool|null
     */
    public function providerInvited(
        string $email,
        string $provider_name,
        string $sponsor_name,
        ?string $sponsor_phone,
        ?string $sponsor_email,
        string $fund_name,
        string $fund_start_date,
        string $fund_end_date,
        string $from_fund_name,
        string $invitation_link
    ) {
        return $this->sendMail($email, new ProviderInvitedMail(
            $provider_name,
            $sponsor_name,
            $sponsor_phone,
            $sponsor_email,
            $fund_name,
            $fund_start_date,
            $fund_end_date,
            $from_fund_name,
            $invitation_link
        ));
    }

    /**
     * Notify provider that his request to apply for fund was approved
     *
     * @param string $email
     * @param $identifier
     * @param string $fund_name
     * @param string $provider_name
     * @param string $sponsor_name
     * @param string $provider_dashboard_link
     * @return bool
     */
    public function providerApproved(
        string $email,
        $identifier,
        string $fund_name,
        string $provider_name,
        string $sponsor_name,
        string $provider_dashboard_link
    ) {
        return $this->sendMail($email, new ProviderApprovedMail(
            $fund_name,
            $provider_name,
            $sponsor_name,
            $provider_dashboard_link,
            $identifier
        ));
    }

    /**
     * Notify provider that his request to apply for fund was rejected
     *
     * @param string $email
     * @param $identifier
     * @param string $fund_name
     * @param string $provider_name
     * @param string $sponsor_name
     * @param string $phone_number
     * @return bool
     */
    public function providerRejected(
        string $email,
        $identifier,
        string $fund_name,
        string $provider_name,
        string $sponsor_name,
        string $phone_number
    ) {
        return $this->sendMail($email, new ProviderRejectedMail(
            $fund_name,
            $provider_name,
            $sponsor_name,
            $phone_number,
            $identifier
        ));
    }

    /**
     * Notify user that now he can validate records for the given sponsor
     *
     * @param string $email
     * @param $identifier
     * @param string $sponsor_name
     * @return bool
     */
    public function youAddedAsValidator(
        string $email,
        $identifier,
        string $sponsor_name
    ) {
        return $this->sendMail($email, new AddedAsValidatorMail(
            $sponsor_name,
            $identifier
        ));
    }

    /**
     * Notify user about new validation request on validation dashboard
     *
     * @param string $email
     * @param $identifier
     * @param string $validator_dashboard_link
     * @return bool
     */
    public function newValidationRequest(
        string $email,
        $identifier,
        string $validator_dashboard_link
    ) {
        return $this->sendMail($email, new NewValidationRequestMail(
            $validator_dashboard_link,
            $identifier
        ));
    }


    /**
     * Notify provider about new fund available for sign up
     *
     * @param string $email
     * @param $identifier
     * @param string $fund_name
     * @param string $provider_dashboard_link
     * @return bool
     */
    public function newFundApplicable(
        string $email,
        $identifier,
        string $fund_name,
        string $provider_dashboard_link
    ) {
        return $this->sendMail($email, new NewFundApplicableMail(
            $fund_name,
            $provider_dashboard_link,
            $identifier
        ));
    }

    /**
     * Notify user that new fund was created
     *
     * @param string $email
     * @param $identifier
     * @param string $fund_name
     * @param string $webshop_link
     * @return bool
     */
    public function newFundCreated(
        string $email,
        $identifier,
        string $fund_name,
        string $webshop_link
    ) {
        return $this->sendMail($email, new FundCreatedMail(
            $fund_name,
            $webshop_link,
            $identifier
        ));
    }

    /**
     * Notify user that new fund request was created
     *
     * @param string $email
     * @param $identifier
     * @param string $fund_name
     * @param string $webshop_link
     * @return bool
     */
    public function newFundRequestCreated(
        string $email,
        $identifier,
        string $fund_name,
        string $webshop_link
    ) {
        return $this->sendMail($email, new FundRequestCreatedMail(
            $fund_name,
            $webshop_link,
            $identifier
        ));
    }

    /**
     * Notify user that fund request resolved
     *
     * @param string $email
     * @param $identifier
     * @param string $requestStatus
     * @param string $fundName
     * @param string $webshopLink
     * @return bool|null
     */
    public function fundRequestResolved(
        string $email,
        $identifier,
        string $requestStatus,
        string $fundName,
        string $webshopLink
    ) {
        return $this->sendMail($email, new FundRequestResolvedMail(
            $requestStatus,
            $fundName,
            $webshopLink,
            $identifier
        ));
    }

    /**
     * Notify user that fund request record declined
     *
     * @param string $email
     * @param $identifier
     * @param string $rejectionNote
     * @param string $fundName
     * @param string $webshopLink
     * @return bool|null
     */
    public function fundRequestRecordDeclined(
        string $email,
        $identifier,
        ?string $rejectionNote,
        string $fundName,
        string $webshopLink
    ) {
        return $this->sendMail($email, new FundRequestRecordDeclinedMail(
            $fundName,
            $rejectionNote,
            $webshopLink,
            $identifier
        ));
    }

    /**
     * Notify user that new fund request clarification requested
     *
     * @param string $email
     * @param $identifier
     * @param string $fundName
     * @param string $question
     * @param string $webshopClarificationLink
     * @param string $webshopLink
     * @return bool|null
     */
    public function sendFundRequestClarificationToRequester(
        string $email,
        $identifier,
        string $fundName,
        string $question,
        string $webshopClarificationLink,
        string $webshopLink
    ) {
        return $this->sendMail($email, new FundRequestClarificationRequestedMail(
            $fundName,
            $question,
            $webshopClarificationLink,
            $webshopLink,
            $identifier
        ));
    }

    /**
     * Notify providers that new fund was started
     *
     * @param string $email
     * @param $identifier
     * @param string $fund_name
     * @param string $sponsor_name
     * @return bool
     */
    public function newFundStarted(
        string $email,
        $identifier,
        string $fund_name,
        string $sponsor_name
    ) {
        return $this->sendMail($email, new FundStartedMail(
            $fund_name,
            $sponsor_name,
            $identifier
        ));
    }

    /**
     * Notify company that new fund was created
     *
     * @param string $email
     * @param string $fund_name
     * @param string $organization_name
     * @return bool
     */
    public function newFundCreatedNotifyCompany(
        string $email,
        string $fund_name,
        string $organization_name
    ) {
        return $this->sendMail($email, new ForusFundCreated(
            $fund_name,
            $organization_name
        ));
    }

    /**
     * Notify users that fund was closed
     *
     * @param string $email
     * @param string $fund_name
     * @param string $fund_end_date
     * @param string $fund_contact
     * @param string $sponsor_name
     * @param string $webshop_link
     * @return bool|null
     */
    public function fundClosed(
        string $email,
        string $fund_name,
        string $fund_end_date,
        string $fund_contact,
        string $sponsor_name,
        string $webshop_link
    ) {
        return $this->sendMail($email, new FundClosed(
            $fund_name,
            $fund_end_date,
            $fund_contact,
            $sponsor_name,
            $webshop_link
        ));
    }

    /**
     * Notify providers that fund was closed
     *
     * @param string $email
     * @param string $fund_name
     * @param string $fund_end_date
     * @param string $sponsor_name
     * @param string $dashboard_link
     * @return bool|null
     */
    public function fundClosedProvider(
        string $email,
        string $fund_name,
        string $fund_end_date,
        string $sponsor_name,
        string $dashboard_link
    ) {
        return $this->sendMail($email, new FundClosedProvider(
            $fund_name,
            $fund_end_date,
            $sponsor_name,
            $dashboard_link
        ));
    }

    /**
     * Send number of fund users
     *
     * @param string $email
     * @param string $fund_name
     * @param string $sponsor_name
     * @param int $sponsor_amount
     * @param int $provider_amount
     * @param int $requester_amount
     * @return bool
     */
    public function sendFundUserStatisticsReport(
        string $email,
        string $fund_name,
        string $sponsor_name,
        int $sponsor_amount,
        int $provider_amount,
        int $requester_amount
    ) {
        return $this->sendMail($email, new FundStatisticsMail(
            $fund_name,
            $sponsor_name,
            $sponsor_amount,
            $provider_amount,
            $requester_amount,
            $sponsor_amount + $provider_amount + $requester_amount
        ));
    }

    /**
     * Notify sponsor that new product added by approved provider
     *
     * @param string $email
     * @param $identifier
     * @param string $sponsor_name
     * @param string $fund_name
     * @param string $webshop_link
     * @return bool
     */
    public function newProductAdded(
        string $email,
        $identifier,
        string $sponsor_name,
        string $fund_name,
        string $webshop_link
    ) {
        return $this->sendMail($email, new ProductAddedMail(
            $sponsor_name,
            $fund_name,
            $webshop_link,
            $identifier
        ));
    }

    /**
     * Send voucher by email
     *
     * @param string $email
     * @param $identifier
     * @param string $fund_name
     * @param string $fund_product_name
     * @param string $qr_token
     *
     * @return bool
     */
    public function sendVoucher(
        string $email,
        $identifier,
        string $fund_name,
        int $voucher_amount,
        string $voucher_expire_minus_day,
        string $fund_product_name,
        string $qr_token
    ): bool {
        return $this->sendMail($email, new SendVoucherMail(
            $fund_name,
            $fund_product_name,
            $qr_token,
            $voucher_amount,
            $voucher_expire_minus_day,
            $identifier
        ));
    }

    /**
     * Send assigned voucher to email
     *
     * @param string $email
     * @param $identifier
     * @param string $fund_name
     * @param int $voucher_amount
     * @param string $voucher_expire_minus_day
     * @param string $qr_token
     * @return bool
     */
    public function assignVoucher(
        string $email,
        $identifier,
        string $fund_name,
        int $voucher_amount,
        string $voucher_expire_minus_day,
        string $qr_token
    ): bool {
        return $this->sendMail($email, new AssignedVoucherMail(
            $fund_name,
            $qr_token,
            $voucher_amount,
            $voucher_expire_minus_day,
            $identifier
        ));
    }

    /**
     * Send voucher by email
     *
     * @param string $email
     * @param $identifier
     * @param string $requester_email
     * @param string $product_name
     * @param string $qr_token
     * @param string $reason
     * @return bool
     */
    public function shareProductVoucher(
        string $email,
        $identifier,
        string $requester_email,
        string $product_name,
        string $qr_token,
        string $reason
    ) {
        return $this->sendMail($email, new ShareProductVoucherMail(
            $requester_email,
            $product_name,
            $qr_token,
            $reason,
            $identifier
        ));
    }

    /**
     * Send restore identity link to address email
     *
     * @param string $email
     * @param $identifier
     * @param string $link
     * @param string $platform
     * @return bool
     */
    public function loginViaEmail(
        string $email,
        $identifier,
        string $link,
        string $platform
    ) {
        return $this->sendMail($email, new UserLoginMail(
            $link,
            $platform,
            $identifier
        ));
    }

    /**
     * New transaction was made send current voucher balance
     *
     * @param string $email
     * @param $identifier
     * @param string $fund_name
     * @param string $current_budget
     * @return bool
     */
    public function sendVoucherAmountLeftEmail(
        string $email,
        $identifier,
        string $fund_name,
        string $current_budget
    ) {
        return $this->sendMail($email, new PaymentSuccessMail(
            $fund_name,
            $current_budget,
            $identifier
        ));
    }

    /**
     * Notify provider that a product was reserved and customer will come by
     * in shop to pickup the product or service.
     *
     * @param string $email
     * @param $identifier
     * @param string $product_name
     * @param string $expiration_date
     * @return bool
     */
    public function productReserved(
        string $email,
        $identifier,
        string $product_name,
        string $expiration_date
    ) {
        return $this->sendMail($email, new ProductReservedMail(
            $product_name,
            $expiration_date,
            $identifier
        ));
    }

    /**
     * Notify provider that a product was reserved and customer will come by
     * in shop to pickup the product or service.
     *
     * @param string $email
     * @param $identifier
     * @param string $product_name
     * @param string $product_price
     * @param string $provider_phone
     * @param string $provider_email
     * @param string $qr_token
     * @param string $provider_organization_name
     * @param string $expire_at_minus_1_day
     * @return bool|null
     */
    public function productReservedUser(
        string $email,
        $identifier,
        string $product_name,
        string $product_price,
        string $provider_phone,
        string $provider_email,
        string $qr_token,
        string $provider_organization_name,
        string $expire_at_minus_1_day
    ) {
        return $this->sendMail($email, new ProductReservedUserMail(
            $product_name,
            $product_price,
            $provider_phone,
            $provider_email,
            $qr_token,
            $provider_organization_name,
            $expire_at_minus_1_day,
            $identifier
        ));
    }

    /**
     * Notify provider that a product was sold out.
     *
     * @param string $email
     * @param $identifier
     * @param string $product_name
     * @param string $sponsor_dashboard_url
     * @return bool
     */
    public function productSoldOut(
        string $email,
        $identifier,
        string $product_name,
        string $sponsor_dashboard_url
    ) {
        return $this->sendMail($email, new ProductSoldOutMail(
            $product_name,
            $sponsor_dashboard_url,
            $identifier
        ));
    }

    /**
     * Send email confirmation link
     *
     * @param string $email
     * @param $identifier
     * @param string $confirmationLink
     * @return bool
     */
    public function sendEmailConfirmationLink(
        string $email,
        string $confirmationLink,
        $identifier
    ) {
        return $this->sendMail($email, new EmailActivationMail(
            config('app.name'),
            $confirmationLink,
            $identifier
        ));
    }

    /**
     * @param string $orgName
     * @param string $email
     * @param string $confirmationLink
     * @param $identifier
     * @return bool|null
     */
    public function sendEmailEmployeeAdded(
        string $orgName,
        string $email,
        string $confirmationLink,
        $identifier
    ) {
        return $this->sendMail($email, new EmployeeAddedMail(
            $orgName,
            config('app.name'),
            $confirmationLink,
            $identifier
        ));
    }

    /**
     * Notify user that voucher is about to expire
     *
     * @param string $email
     * @param string $fund_name
     * @param string $sponsor_name
     * @param string $start_date
     * @param string $end_date
     * @param string $sponsor_phone
     * @param string $sponsor_email
     * @param string $webshopLink
     * @return bool
     */
    public function voucherExpireSoon(
        string $email,
        string $fund_name,
        string $sponsor_name,
        string $start_date,
        string $end_date,
        string $sponsor_phone,
        string $sponsor_email,
        string $webshopLink
    ) {
        return $this->sendMail($email, new FundExpiredMail(
            $fund_name,
            $sponsor_name,
            $start_date,
            $end_date,
            $sponsor_name,
            $sponsor_phone,
            $sponsor_email,
            $webshopLink
        ));
    }

    /**
     * Fund balance reached the threshold set in preferences
     *
     * @param string $email
     * @param $identifier
     * @param string $link
     * @param string $sponsor_name
     * @param string $fund_name
     * @param string $notification_amount
     * @param string $budget_left
     * @param string $iban
     * @param string $topup_code
     * @return bool|null
     */
    public function fundBalanceWarning(
        string $email,
        $identifier,
        string $link,
        string $sponsor_name,
        string $fund_name,
        string $notification_amount,
        string $budget_left,
        string $iban,
        string $topup_code
    ): bool {
        return $this->sendMail($email, new FundBalanceWarningMail(
            $fund_name,
            $sponsor_name,
            $notification_amount,
            $budget_left,
            $link,
            $iban,
            $topup_code,
            $identifier
        ));
    }

    /**
     * Send the mail and check for failure
     *
     * @param $email
     * @param Mailable $mailable
     * @return bool|null
     */
    private function sendMail($email, Mailable $mailable) {
        if (config()->get('mail.disable', false)) {
            return true;
        }

        try {
            if ($this->isUnsubscribed($email, $mailable)) {
                return null;
            }

            $unsubscribeLink = $this->notificationRepo->makeUnsubLink($email);
            $notificationPreferencesLink = sprintf(
                '%s/%s',
                rtrim(Implementation::active()['url_sponsor'], '/'),
                'email/preferences');

            /** @var Queueable|Mailable $message */
            $message = $mailable->with(compact(
                'email', 'unsubscribeLink', 'notificationPreferencesLink'
            ));

            $message = $message->onQueue(env('EMAIL_QUEUE_NAME', 'emails'));

            $this->mailer->to($email)->queue($message);

            return $this->checkFailure(get_class($mailable));
        } catch (\Exception $exception) {
            $this->logFailure($exception);
            return false;
        }
    }

    /**
     * Check if email is unsubscribed from all message or current email type
     * @param string $email
     * @param Mailable $mailable
     * @return bool
     * @throws \Exception
     */
    protected function isUnsubscribed(string $email, Mailable $mailable) {
        $mailClass = get_class($mailable);

        return $this->notificationRepo->isMailUnsubscribable($mailClass) && (
            $this->notificationRepo->isEmailUnsubscribed($email) ||
            $this->notificationRepo->isEmailTypeUnsubscribed(
                $this->recordRepo->identityAddressByEmail($email),
                $mailClass
            )
        );
    }

    /**
     * Check if Push notification can be subscribed
     *
     * @param string $key
     * @return bool
     */
    protected function isPushUnsubscribable(string $key) {
        return $this->notificationRepo->isPushNotificationUnsubscribable($key);
    }

    /**
     * Check if Push notification is unsubscribed
     *
     * @param string $identity_address
     * @param string $key
     * @return bool
     */
    protected function isPushUnsubscribed(string $identity_address, string $key) {
        return $this->notificationRepo->isPushNotificationUnsubscribed(
            $identity_address,
            $key
        );
    }

    /**
     * Check for failure and log in case of error
     *
     * @param string $mailName
     * @return bool
     */
    private function checkFailure(string $mailName): bool
    {
        if (!$this->mailer->failures()) {
            return true;
        }

        $this->logFailure($mailName);

        return false;
    }

    /**
     * Log failure
     *
     * @param string|null $message
     * @return void
     */
    private function logFailure(?string $message)
    {
        logger()->error(sprintf(
            'Error sending notification: `%s`',
            $message
        ));
    }
}
