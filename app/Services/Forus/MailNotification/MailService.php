<?php

namespace App\Services\Forus\MailNotification;

use App\Mail\Auth\UserLogin;
use App\Mail\Funds\ProviderApplied;
use App\Mail\Vouchers\Voucher;
use App\Services\ApiRequestService\ApiRequest;
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

    /** @var ApiRequest $apiRequest  */
    protected $apiRequest;

    /**
     * MailService constructor.
     */
    public function __construct() {
        $this->apiRequest = app()->make('api_request');
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
        $email,
        $identifier,
        string $provider_name,
        string $sponsor_name,
        string $fund_name,
        string $sponsor_dashboard_link
    ) {
        Mail::send(new ProviderApplied(
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
     * @param $identifier
     * @param string $fund_name
     * @param string $provider_name
     * @param string $sponsor_name
     * @param string $provider_dashboard_link
     * @return bool
     */
    public function providerApproved(
        $identifier,
        string $fund_name,
        string $provider_name,
        string $sponsor_name,
        string $provider_dashboard_link
    ) {
        if (!$this->serviceApiUrl) {
            return false;
        }

        $endpoint = $this->getEndpoint('/sender/vouchers/provider_approved/');

        $res = $this->apiRequest->post($endpoint, [
            'reffer_id'                 => $identifier ?? '',
            'fund_name'                 => $fund_name,
            'provider_name'             => $provider_name,
            'sponsor_name'              => $sponsor_name,
            'provider_dashboard_link'   => $provider_dashboard_link,
        ]);

        if ($res->getStatusCode() != 200) {
            app()->make('log')->error(
                sprintf(
                    'Error sending notification `providerApproved`: %s',
                    $res->getBody()
                )
            );

            return false;
        }

        return true;
    }

    /**
     * Notify provider that his request to apply for fund was rejected
     *
     * @param $identifier
     * @param string $fund_name
     * @param string $provider_name
     * @param string $sponsor_name
     * @return bool
     */
    public function providerRejected(
        $identifier,
        string $fund_name,
        string $provider_name,
        string $sponsor_name
    ) {
        if (!$this->serviceApiUrl) {
            return false;
        }

        $endpoint = $this->getEndpoint('/sender/vouchers/provider_rejected/');

        $res = $this->apiRequest->post($endpoint, [
            'reffer_id'     => $identifier ?? '',
            'fund_name'     => $fund_name,
            'provider_name' => $provider_name,
            'sponsor_name'  => $sponsor_name
        ]);

        if ($res->getStatusCode() != 200) {
            app()->make('log')->error(
                sprintf(
                    'Error sending notification `providerRejected`: %s',
                    $res->getBody()
                )
            );

            return false;
        }

        return true;
    }

    /**
     * Notify user that now he can validate records for the given sponsor
     *
     * @param $identifier
     * @param string $sponsor_name
     * @return bool
     */
    public function youAddedAsValidator(
        $identifier,
        string $sponsor_name
    ) {
        if (!$this->serviceApiUrl) {
            return false;
        }

        $endpoint = $this->getEndpoint('/sender/sponsors/you_added_as_validator/');

        $res = $this->apiRequest->post($endpoint, [
            'reffer_id' => $identifier ?? '',
            'sponsore_name' => $sponsor_name
        ]);

        if ($res->getStatusCode() != 200) {
            app()->make('log')->error(
                sprintf(
                    'Error sending notification `youAddedAsValidator`: %s',
                    $res->getBody()
                )
            );

            return false;
        }

        return true;
    }

    /**
     * Notify user about new validation request on validation dashboard
     *
     * @param $identifier
     * @param string $validator_dashboard_link
     * @return bool
     */
    public function newValidationRequest(
        $identifier,
        string $validator_dashboard_link
    ) {
        if (!$this->serviceApiUrl) {
            return false;
        }

        $endpoint = $this->getEndpoint('/sender/validations/new_validation_request/');

        $res = $this->apiRequest->post($endpoint, [
            'reffer_id'                 => $identifier ?? '',
            'validator_dashboard_link'  => $validator_dashboard_link
        ]);

        if ($res->getStatusCode() != 200) {
            app()->make('log')->error(
                sprintf(
                    'Error sending notification `newValidationRequest`: %s',
                    $res->getBody()
                )
            );

            return false;
        }

        return true;
    }


    /**
     * Notify provider about new fund available for sign up
     *
     * @param $identifier
     * @param string $fund_name
     * @param string $provider_dashboard_link
     * @return bool
     */
    public function newFundApplicable(
        $identifier,
        string $fund_name,
        string $provider_dashboard_link
    ) {
        if (!$this->serviceApiUrl) {
            return false;
        }

        $endpoint = $this->getEndpoint('/sender/vouchers/new_fund/');

        $res = $this->apiRequest->post($endpoint, [
            'reffer_id'                 => $identifier ?? '',
            'fund_name'                 => $fund_name,
            'provider_dashboard_link'   => $provider_dashboard_link,
        ]);

        if ($res->getStatusCode() != 200) {
            app()->make('log')->error(
                sprintf(
                    'Error sending notification `newFundApplicable`: %s',
                    $res->getBody()
                )
            );

            return false;
        }

        return true;
    }

    /**
     * Notify user that new fund was created
     *
     * @param $identifier
     * @param string $fund_name
     * @param string $webshop_link
     * @return bool
     */
    public function newFundCreated(
        $identifier,
        string $fund_name,
        string $webshop_link
    ) {
        if (!$this->serviceApiUrl) {
            return false;
        }

        $endpoint = $this->getEndpoint('/sender/vouchers/new_fund_created/');

        resolve('log')->info(collect([$endpoint, [
            'reffer_id'     => $identifier ?? '',
            'fund_name'     => $fund_name,
            'webshop_link'  => $webshop_link,
        ]]));

        $res = $this->apiRequest->post($endpoint, [
            'reffer_id'     => $identifier ?? '',
            'fund_name'     => $fund_name,
            'webshop_link'  => $webshop_link,
        ]);

        if ($res->getStatusCode() != 200) {
            app()->make('log')->error(
                sprintf(
                    'Error sending notification `newFundCreated`: %s',
                    $res->getBody()
                )
            );

            return false;
        }

        return true;
    }


    /**
     * Notify providers that new fund was started
     *
     * @param $identifier
     * @param string $fund_name
     * @param string $sponsor_name
     * @return bool
     */
    public function newFundStarted(
        $identifier,
        string $fund_name,
        string $sponsor_name
    ) {
        if (!$this->serviceApiUrl) {
            return false;
        }

        $endpoint = $this->getEndpoint('/sender/vouchers/fund_started/');

        $res = $this->apiRequest->post($endpoint, [
            'reffer_id'     => $identifier ?? '',
            'fund_name'     => $fund_name,
            'sponsor_name'  => $sponsor_name,
        ]);

        if ($res->getStatusCode() != 200) {
            app()->make('log')->error(
                sprintf(
                    'Error sending notification `newFundStarted`: %s',
                    $res->getBody()
                )
            );

            return false;
        }

        return true;
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
        if (!$this->serviceApiUrl) {
            return false;
        }

        $email = env('EMAIL_FOR_FUND_CREATED', 'demo@forus.io');

        $endpoint = $this->getEndpoint('/sender/vouchers/forus_new_fund_created/');

        $res = $this->apiRequest->post($endpoint, [
            'email'         => $email,
            'fund_name'     => $fund_name,
            'sponsor_name'  => $organization_name,
        ]);

        if ($res->getStatusCode() != 200) {
            app()->make('log')->error(
                sprintf(
                    'Error sending notification `newFundCreated` to forus: %s',
                    $res->getBody()
                )
            );

            return false;
        }

        return true;
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
        if (!$this->serviceApiUrl) {
            return false;
        }

        $email = env('EMAIL_FOR_FUND_CALC', 'demo@forus.io');

        $endpoint = $this->getEndpoint('/sender/vouchers/forus_users_calc/');

        $res = $this->apiRequest->post($endpoint, [
            'email'             => $email,
            'fund_name'         => $fund_name,
            'sponsor_name'      => $sponsor_name,
            'sponsor_amount'    => $sponsor_amount,
            'provider_amount'   => $provider_amount,
            'requester_amount'  => $requester_amount,
            'total_amount'      => $total_amount
        ]);

        if ($res->getStatusCode() != 200) {
            app()->make('log')->error(
                sprintf(
                    'Error sending notification `calculateFundUsers`: %s',
                    $res->getBody()
                )
            );

            return false;
        }

        return true;
    }

    /**
     * Notify sponsor that new product added by provider
     *
     * @param $identifier
     * @param string $sponsor_name
     * @param string $fund_name
     * @return bool
     */
    public function newProductAdded(
        $identifier,
        string $sponsor_name,
        string $fund_name
    ) {
        if (!$this->serviceApiUrl) {
            return false;
        }

        $endpoint = $this->getEndpoint('/sender/vouchers/new_product_added/');

        $res = $this->apiRequest->post($endpoint, [
            'reffer_id'     => $identifier ?? '',
            'sponsor_name'  => $sponsor_name,
            'fund_name'     => $fund_name,
        ]);

        if ($res->getStatusCode() != 200) {
            app()->make('log')->error(
                sprintf(
                    'Error sending notification `newProductAdded`: %s',
                    $res->getBody()
                )
            );

            return false;
        }

        return true;
    }

    /**
     * Send voucher by email
     *
     * @param string $email
     * @param $identifier
     * @param string $fund_product_name
     * @param string $qr_url
     * @param null|string $implementation
     *
     * @return bool
     */
    public function sendVoucher(
        string $email,
        $identifier,
        string $fund_product_name,
        string $qr_url
    ): bool {

        Mail::send(new Voucher(
            $email,
            $fund_product_name,
            $qr_url
        ));

        return $this->checkFailure('Voucher');
    }


    /**
     * Send voucher by email
     *
     * @param $identifier
     * @param string $requester_email
     * @param string $product_name
     * @param string $qr_url
     * @param string $reason
     * @return bool
     */
    public function shareVoucher(
        $identifier,
        string $requester_email,
        string $product_name,
        string $qr_url,
        string $reason
    ) {
        if (!$this->serviceApiUrl) {
            return false;
        }

        $endpoint = $this->getEndpoint('/sender/vouchers/share_product/');

        $res = $this->apiRequest->post($endpoint, [
            'reffer_id'             => $identifier ?? '',
            'product_name'          => $product_name,
            'qr_url'                => $qr_url,
            'requester_email'       => $requester_email,
            'reason'                => $reason
        ]);

        if ($res->getStatusCode() != 200) {
            app()->make('log')->error(
                sprintf(
                    'Error sending notification `shareVoucher`: %s',
                    $res->getBody()
                )
            );

            return false;
        }

        return true;
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
        Mail::send(new UserLogin(
            $email,
            $link,
            $platform,
            $identifier
        ));

        return $this->checkFailure('UserLogin');
    }

    /**
     * @param $identifier
     * @param string $fund_name
     * @param string $current_budget
     * @return bool
     */
    public function transactionAvailableAmount(
        $identifier,
        string $fund_name,
        string $current_budget
    ) {
        if (!$this->serviceApiUrl) {
            return false;
        }

        $endpoint = $this->getEndpoint('/sender/vouchers/payment_success/');

        $res = $this->apiRequest->post($endpoint, [
            'reffer_id'         => $identifier ?? '',
            'fund_name'         => $fund_name,
            'current_budget'    => $current_budget,
        ]);

        if ($res->getStatusCode() != 200) {
            app()->make('log')->error(
                sprintf(
                    'Error sending notification `transactionAvailableAmount`: %s',
                    $res->getBody()
                )
            );

            return false;
        }

        return true;
    }

    /**
     * Notify provider that a product was reserved and customer will come by
     * in shop to pickup the product or service.
     *
     * @param $identifier
     * @param string $product_name
     * @param string $expiration_date
     * @return bool
     */
    public function productReserved(
        $identifier,
        string $product_name,
        string $expiration_date
    ) {
        if (!$this->serviceApiUrl) {
            return false;
        }

        $endpoint = $this->getEndpoint('/sender/vouchers/product_bought/');

        $res = $this->apiRequest->post($endpoint, [
            'reffer_id'         => $identifier ?? '',
            'product_name'      => $product_name,
            'expiration_date'   => $expiration_date,
        ]);

        if ($res->getStatusCode() != 200) {
            app()->make('log')->error(
                sprintf(
                    'Error sending notification `productReserved`: %s',
                    $res->getBody()
                )
            );

            return false;
        }

        return true;
    }

    /**
     * Notify provider that a product was sold out.
     *
     * @param $identifier
     * @param string $product_name
     * @param string $sponsor_dashboard_url
     * @return bool
     */
    public function productSoldOut(
        $identifier,
        string $product_name,
        string $sponsor_dashboard_url
    ) {
        if (!$this->serviceApiUrl) {
            return false;
        }

        $endpoint = $this->getEndpoint('/sender/vouchers/product_soldout/');

        $res = $this->apiRequest->post($endpoint, [
            'reffer_id'             => $identifier ?? '',
            'product_name'          => $product_name,
            'sponsor_dashboard_url' => $sponsor_dashboard_url,
        ]);

        if ($res->getStatusCode() != 200) {
            app()->make('log')->error(
                sprintf(
                    'Error sending notification `productSoldOut`: %s',
                    $res->getBody()
                )
            );

            return false;
        }

        return true;
    }

    /**
     * Send email confirmation link by identity address
     * @param $identifier
     * @param string $confirmationLink
     * @return bool
     */
    public function sendEmailConfirmationToken(
        $identifier,
        string $confirmationLink
    ) {
        $platform = env('APP_NAME');

        if (!$this->serviceApiUrl) {
            return false;
        }

        $endpoint = $this->getEndpoint('/sender/user/email_activation');

        $res = $this->apiRequest->post($endpoint, [
            'reffer_id' => $identifier ?? '',
            'platform'  => $platform,
            'link'      => $confirmationLink,
        ]);

        if ($res->getStatusCode() != 200) {
            app()->make('log')->error(
                sprintf(
                    'Error sending notification `sendPrimaryEmailConfirmation`: %s',
                    $res->getBody()
                )
            );

            return false;
        }

        return true;
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
    )
    {
        if (!$this->serviceApiUrl) {
            return false;
        }

        $endpoint = $this->getEndpoint('/sender/vouchers/fund_expires');

        $res = $this->apiRequest->post($endpoint, [
            'email'                     => $email,
            'fund_name'                 => $fund_name,
            'sponsor_name'              => $sponsor_name,
            'start_date_fund'           => $start_date,
            'end_date_fund'             => $end_date,
            'phonenumber_sponsor'       => $sponsor_phone,
            'emailaddress_sponsor'      => $sponsor_email,
            'shop_implementation_url'   => $webshopLink
        ]);

        if ($res->getStatusCode() != 200) {
            app()->make('log')->error(
                sprintf(
                    'Error sending notification `voucherExpire`: %s',
                    $res->getBody()
                )
            );

            return false;
        }

        return true;
    }

    /**
     * @param $identifier
     * @param string $link
     * @param string $sponsor_name
     * @param string $fund_name
     * @param string $notification_amount
     * @return bool
     */
    public function fundNotifyReachedNotificationAmount(
        $identifier,
        string $link,
        string $sponsor_name,
        string $fund_name,
        string $notification_amount
    )
    {
        if (!$this->serviceApiUrl) {
            return false;
        }

        $endpoint = $this->getEndpoint('/sender/vouchers/fund_balance_warning');

        $res = $this->apiRequest->post($endpoint, [
            'reffer_id'                 => $identifier ?? '',
            'fund_name'                 => $fund_name,
            'sponsor_name'              => $sponsor_name,
            'treshold_amount'           => $notification_amount,
            'sponsor_dashboard_link'    => $link
        ]);

        if ($res->getStatusCode() != 200) {
            app()->make('log')->error(
                sprintf(
                    'Error sending notification `fundNotifyReachedNotificationAmount`: %s',
                    $res->getBody()
                )
            );

            return false;
        }

        return true;
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
