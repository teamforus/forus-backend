<?php

namespace App\Services\Forus\Notification;

use App\Mail\Auth\UserLoginMail;
use App\Mail\FundRequests\FundRequestCreatedMail;
use App\Mail\FundRequests\FundRequestClarificationRequestedMail;
use App\Mail\FundRequests\FundRequestRecordDeclinedMail;
use App\Mail\FundRequests\FundRequestResolvedMail;
use App\Mail\Funds\FundBalanceWarningMail;
use App\Mail\Funds\FundCreatedMail;
use App\Mail\Funds\FundExpiredMail;
use App\Mail\Funds\FundStartedMail;
use App\Mail\Funds\NewFundApplicableMail;
use App\Mail\Funds\ProductAddedMail;
use App\Mail\Funds\ProviderAppliedMail;
use App\Mail\Funds\ProviderApprovedMail;
use App\Mail\Funds\ProviderRejectedMail;
use App\Mail\Funds\Forus\ForusFundCreated;
use App\Mail\User\EmailActivationMail;
use App\Mail\Validations\AddedAsValidatorMail;
use App\Mail\Validations\NewValidationRequestMail;
use App\Mail\Vouchers\FundStatisticsMail;
use App\Mail\Vouchers\PaymentSuccessMail;
use App\Mail\Vouchers\ProductReservedMail;
use App\Mail\Vouchers\ProductSoldOutMail;
use App\Mail\Vouchers\ShareProductVoucherMail;
use App\Mail\Vouchers\SendVoucherMail;
use App\Models\Implementation;
use App\Services\ApiRequestService\ApiRequest;
use App\Services\Forus\Notification\Interfaces\INotificationRepo;
use App\Services\Forus\Record\Repositories\Interfaces\IRecordRepo;
use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Mail\Mailable;

/**
 * Class MailService
 * @package App\Services\Forus\MailNotification
 */
class NotificationService
{
    const TYPE_EMAIL = 1;
    const TYPE_PUSH_ANDROID = 2;
    const TYPE_PUSH_IOS = 3;

    protected $notificationRepo;
    protected $serviceApiUrl;
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
     * Get endpoint url
     *
     * @param string $uri
     * @param string|null $locale
     * @return string
     */
    private function getEndpoint(
        string $uri,
        string $locale = null
    ) {
        if (!$locale) {
            $locale = config('app.locale', 'en');
        }

        return $this->serviceApiUrl . '/' . $locale . $uri;
    }

    public static function typeCodeToString(int $code) {
        switch ($code) {
            case self::TYPE_EMAIL: return "Email"; break;
            case self::TYPE_PUSH_ANDROID: return "Push message android"; break;
            case self::TYPE_PUSH_IOS: return "Push message ios"; break;
        }

        return "Unknown";
    }

    /**
     * Register new connection for given identifier
     *
     * @param $identifier
     * @param int $type
     * @param string $value
     * @return bool
     */
    public function addConnection(
        $identifier,
        int $type,
        string $value
    ) {
        if (!$this->serviceApiUrl) {
            return false;
        }

        $endpoint = $this->getEndpoint('/user/connections/add/', 'en');

        $res = $this->apiRequest->post($endpoint, [
            'user_id'   => $identifier ?? '',
            'type'      => $type,
            'value'     => $value,
        ]);

        if ($res->getStatusCode() != 201) {
            app()->make('log')->error(
                sprintf(
                    'Error storing user %s contacts: %s',
                    self::typeCodeToString($type),
                    $res->getBody()
                )
            );

            return false;
        }

        return true;
    }

    /**
     * Register new connection for given identifier
     *
     * @param $identifier
     * @param string $value
     * @return bool
     */
    public function deleteConnection(
        $identifier,
        string $value
    ) {
        if (!$this->serviceApiUrl) {
            return false;
        }

        $endpoint = $this->getEndpoint('/user/connections/remove/', 'en');

        $res = $this->apiRequest->post($endpoint, [
            'user_id'   => $identifier ?? '',
            'value'     => $value,
        ]);

        if ($res->getStatusCode() != 200) {
            app()->make('log')->error(
                sprintf(
                    'Error removing user push token: %s',
                    $res->getBody()
                )
            );

            return false;
        }

        return true;
    }

    /**
     * Send push notification
     *
     * @param $identifier
     * @param string $title
     * @param string $body
     * @return bool
     */
    public function sendPushNotification(
        $identifier,
        string $title,
        string $body
    ) {
        if (!$this->serviceApiUrl) {
            return false;
        }

        $endpoint = $this->getEndpoint('/sender/mobile/push/', 'en');

        $res = $this->apiRequest->post($endpoint, [
            'reffer_id' => $identifier ?? '',
            'title'     => $title,
            'body'      => $body,
        ]);

        if ($res->getStatusCode() != 200) {
            app()->make('log')->error(
                sprintf(
                    'Error sending notification `sendPushNotification`: %s',
                    $res->getBody()
                )
            );

            return false;
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
            $identifier,
            $provider_name,
            $sponsor_name,
            $fund_name,
            $sponsor_dashboard_link
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
     * @return bool
     */
    public function newProductAdded(
        string $email,
        $identifier,
        string $sponsor_name,
        string $fund_name
    ) {
        return $this->sendMail($email, new ProductAddedMail(
            $sponsor_name,
            $fund_name,
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
     * @param string $qr_url
     *
     * @return bool
     */
    public function sendVoucher(
        string $email,
        $identifier,
        string $fund_name,
        string $fund_product_name,
        string $qr_url
    ): bool {
        return $this->sendMail($email, new SendVoucherMail(
            $fund_name,
            $fund_product_name,
            $qr_url,
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
     * @param string $qr_url
     * @param string $reason
     * @return bool
     */
    public function shareProductVoucher(
        string $email,
        $identifier,
        string $requester_email,
        string $product_name,
        string $qr_url,
        string $reason
    ) {
        return $this->sendMail($email, new ShareProductVoucherMail(
            $requester_email,
            $product_name,
            $qr_url,
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
     * @return bool|null
     */
    public function fundBalanceWarning(
        string $email,
        $identifier,
        string $link,
        string $sponsor_name,
        string $fund_name,
        string $notification_amount,
        string $budget_left
    ): bool {
        return $this->sendMail($email, new FundBalanceWarningMail(
            $fund_name,
            $sponsor_name,
            $notification_amount,
            $budget_left,
            $link,
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

            $this->mailer->send($mailable->to($email)->with(compact(
                'email', 'unsubscribeLink', 'notificationPreferencesLink'
            )));

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
