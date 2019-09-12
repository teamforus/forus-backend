<?php

namespace App\Services\Forus\MailNotification;

use App\Mail\Auth\UserLoginMail;
use App\Mail\Funds\BalanceWarningMail;
use App\Mail\Funds\FundCreatedMail;
use App\Mail\Funds\FundExpiredMail;
use App\Mail\Funds\FundStartedMail;
use App\Mail\Funds\NewFundApplicableMail;
use App\Mail\Funds\ProductAddedMail;
use App\Mail\Funds\ProviderAppliedMail;
use App\Mail\Funds\ProviderApprovedMail;
use App\Mail\Funds\ProviderRejectedMail;
use App\Mail\Funds\Forus\FundCreatedMail as ForusFundCreated;
use App\Mail\User\EmailActivationMail;
use App\Mail\Validations\AddedAsValidatorMail;
use App\Mail\Validations\NewValidationRequestMail;
use App\Mail\Vouchers\FundStatisticsMail;
use App\Mail\Vouchers\PaymentSuccessMail;
use App\Mail\Vouchers\ProductBoughtMail;
use App\Mail\Vouchers\ProductSoldOutMail;
use App\Mail\Vouchers\ShareProductMail;
use App\Mail\Vouchers\VoucherMail;
use Illuminate\Support\Facades\Mail;

/**
 * Class MailService
 * @package App\Services\Forus\MailNotification
 */
class MailService
{
    const TYPE_EMAIL = 1;
    const TYPE_PUSH_ANDROID = 2;
    const TYPE_PUSH_IOS = 3;

    protected $serviceApiUrl;
    protected $apiRequest;

    /**
     * MailService constructor.
     */
    public function __construct() {
        $this->apiRequest = resolve('api_request');
        $this->serviceApiUrl = env('SERVICE_EMAIL_URL', false);
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
     * Register new email connection for given identifier
     *
     * @param $identifier
     * @param string $email_address
     */
    public function addEmailConnection(
        $identifier,
        string $email_address
    ) {
        $this->addConnection($identifier,self::TYPE_EMAIL, $email_address);
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
     * @param $email
     * @param $identifier
     * @param string $provider_name
     * @param string $sponsor_name
     * @param string $fund_name
     * @param string $sponsor_dashboard_link
     * @return bool
     */
    public function providerApplied(
        string $email,
        $identifier,
        string $provider_name,
        string $sponsor_name,
        string $fund_name,
        string $sponsor_dashboard_link
    ) {
        Mail::send(new ProviderAppliedMail(
            $email,
            $identifier,
            $provider_name,
            $sponsor_name,
            $fund_name,
            $sponsor_dashboard_link
        ));
        return $this->checkFailure('ProviderApplied');
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
        Mail::send(new ProviderApprovedMail(
            $email,
            $fund_name,
            $provider_name,
            $sponsor_name,
            $provider_dashboard_link,
            $identifier
        ));

        return $this->checkFailure('ProviderApproved');
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
     * @return void
     */
    public function providerRejected(
        string $email,
        $identifier,
        string $fund_name,
        string $provider_name,
        string $sponsor_name,
        string $phone_number
    ) {
        Mail::send( new ProviderRejectedMail(
            $email,
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
    ){
        Mail::send(new AddedAsValidatorMail(
            $email,
            $sponsor_name,
            $identifier
        ));

        return $this->checkFailure('AddedAsValidator');
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
        Mail::send( new NewValidationRequestMail(
            $email,
            $validator_dashboard_link,
            $identifier
        ));

        return $this->checkFailure('NewValidationRequest');
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
        Mail::send(new NewFundApplicableMail(
            $email,
            $fund_name,
            $provider_dashboard_link,
            $identifier
        ));

        return $this->checkFailure(('NewFundApplicable'));
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
    ){
        Mail::send(new FundCreatedMail(
            $email,
            $fund_name,
            $webshop_link,
            $identifier
        ));

        return $this->checkFailure('FundCreated');
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
        Mail::send(new FundStartedMail(
            $email,
            $fund_name,
            $sponsor_name,
            $identifier
        ));

        return $this->checkFailure('FundStarted');
    }

    /**
     * Notify company that new fund was created
     *
     * @param string $fund_name
     * @param string $organization_name
     * @return bool
     */
    public function newFundCreatedNotifyCompany(
        string $fund_name,
        string $organization_name
    ) {
        $email = env('EMAIL_FOR_FUND_CREATED', 'demo@forus.io');

        Mail::send(new ForusFundCreated(
            $email,
            $fund_name,
            $organization_name
        ));

        return $this->checkFailure('Forus/FundCreated');
    }

    /**
     * @param string $fund_name
     * @param string $sponsor_name
     * @param int $sponsor_amount
     * @param int $provider_amount
     * @param int $requester_amount
     * @param int $total_amount
     * @return bool
     */
    public function calculateFundUsers(
        string $fund_name,
        string $sponsor_name,
        int $sponsor_amount,
        int $provider_amount,
        int $requester_amount,
        int $total_amount
    ) {
        $email = env('EMAIL_FOR_FUND_CALC', 'demo@forus.io');

        Mail::send(new FundStatisticsMail(
            $email,
            $fund_name,
            $sponsor_name,
            $sponsor_amount,
            $provider_amount,
            $requester_amount,
            $total_amount
        ));

        return $this->checkFailure('FundStatistics');
    }

    /**
     * Notify sponsor that new product added by provider
     *
     * @param string $email
     * @param $identifier
     * @param string $sponsor_name
     * @param string $fund_name
     * @return void
     */
    public function newProductAdded(
        string $email,
        $identifier,
        string $sponsor_name,
        string $fund_name
    ) {
        Mail::send(new ProductAddedMail(
            $email,
            $sponsor_name,
            $fund_name,
            $identifier
        ));

        $this->checkFailure('ProductAdded');
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

        Mail::send(new VoucherMail(
            $email,
            $fund_name,
            $fund_product_name,
            $qr_url,
            $identifier
        ));

        return $this->checkFailure('Voucher');
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
     * @return void
     */
    public function shareVoucher(
        string $email,
        $identifier,
        string $requester_email,
        string $product_name,
        string $qr_url,
        string $reason
    ) {
        Mail::send(new ShareProductMail(
            $email,
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
     *
     * @return bool
     */
    public function loginViaEmail(
        string $email,
        $identifier,
        string $link,
        string $platform
    ) {
        Mail::send(new UserLoginMail(
            $email,
            $link,
            $platform,
            $identifier
        ));

        return $this->checkFailure('UserLogin');
    }

    /**
     * @param string $email
     * @param $identifier
     * @param string $fund_name
     * @param string $current_budget
     * @return bool
     */
    public function transactionAvailableAmount(
        string $email,
        $identifier,
        string $fund_name,
        string $current_budget
    ) {
        Mail::send(new PaymentSuccessMail(
            $email,
            $fund_name,
            $current_budget,
            $identifier
        ));

        return $this->checkFailure('PaymentSuccess');
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
        Mail::send(new ProductBoughtMail(
            $email,
            $product_name,
            $expiration_date,
            $identifier
        ));

        return $this->checkFailure('ProductBought');
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
        Mail::send(new ProductSoldOutMail(
            $email,
            $product_name,
            $sponsor_dashboard_url,
            $identifier
        ));

        return $this->checkFailure('ProductSoldOut');
    }

    /**
     * Send email confirmation link by identity address
     * @param string $email
     * @param $identifier
     * @param string $confirmationLink
     * @return bool
     */
    public function sendEmailConfirmationToken(
        string $email,
        string $confirmationLink,
        $identifier
    ) {
        $platform = env('APP_NAME');

        Mail::send(new EmailActivationMail(
            $email,
            $platform,
            $confirmationLink,
            $identifier
        ));

        return $this->checkFailure('EmailActivation');
    }

    /**
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
    public function voucherExpire(
        string $email,
        string $fund_name,
        string $sponsor_name,
        string $start_date,
        string $end_date,
        string $sponsor_phone,
        string $sponsor_email,
        string $webshopLink
    ){
        Mail::send(new FundExpiredMail(
            $email,
            $fund_name,
            $sponsor_name,
            $start_date,
            $end_date,
            $sponsor_name,
            $sponsor_phone,
            $sponsor_email,
            $webshopLink
        ));

        return $this->checkFailure('FundExpired');
    }

    public function fundNotifyReachedNotificationAmount(
        string $email,
        $identifier,
        string $link,
        string $sponsor_name,
        string $fund_name,
        string $notification_amount
    ): bool {
        Mail::send(new BalanceWarningMail(
            $email,
            $fund_name,
            $sponsor_name,
            $notification_amount,
            $link,
            $identifier
        ));

        return $this->checkFailure('BalanceWarning');
    }

    private function checkFailure(string $mailName): bool
    {
        if (Mail::failures()) {
            app()->make('log')->error(
                sprintf(
                    'Error sending notification `%s`',
                    $mailName
                )
            );

            return false;
        }

        return true;
    }
}
