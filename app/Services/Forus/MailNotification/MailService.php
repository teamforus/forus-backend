<?php

namespace App\Services\Forus\MailNotification;

use App\Services\ApiRequestService\ApiRequest;

/**
 * Class MailService
 * @package App\Services\Forus\MailNotification
 */
class MailService
{
    const TYPE_EMAIL = 1;
    const TYPE_PUSH_MESSAGE = 2;

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
            case self::TYPE_PUSH_MESSAGE: return "Push message"; break;
        }

        return "Unknown";
    }

    /**
     * Register new connection for given identifier
     *
     * @param string $identifier
     * @param int $type
     * @param string $value
     * @return bool
     */
    public function addConnection(
        string $identifier,
        int $type,
        string $value
    ) {
        if (!$this->serviceApiUrl) {
            return false;
        }

        $endpoint = $this->getEndpoint('/user/connections/add/', 'en');

        $res = $this->apiRequest->post($endpoint, [
            'user_id'   => $identifier,
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
     * Notify sponsor that new provider applied to his fund
     *
     * @param string $identifier
     * @param string $provider_name
     * @param string $sponsor_name
     * @param string $fund_name
     * @param string $sponsor_dashboard_link
     * @return bool
     */
    public function providerApplied(
        string $identifier,
        string $provider_name,
        string $sponsor_name,
        string $fund_name,
        string $sponsor_dashboard_link
    ) {
        if (!$this->serviceApiUrl) {
            return false;
        }

        $endpoint = $this->getEndpoint('/sender/vouchers/provider_applied/');

        $res = $this->apiRequest->post($endpoint, [
            'reffer_id'                 => $identifier,
            'provider_name'             => $provider_name,
            'sponsor_name'              => $sponsor_name,
            'fund_name'                 => $fund_name,
            'sponsor_dashboard_link'    => $sponsor_dashboard_link,
        ]);

        if ($res->getStatusCode() != 200) {
            app()->make('log')->error(
                sprintf(
                    'Error sending notification `providerApplied`: %s',
                    $res->getBody()
                )
            );

            return false;
        }

        return true;
    }

    /**
     * Notify provider that his request to apply for fund was approved
     *
     * @param string $identifier
     * @param string $fund_name
     * @param string $provider_name
     * @param string $sponsor_name
     * @return bool
     */
    public function providerApproved(
        string $identifier,
        string $fund_name,
        string $provider_name,
        string $sponsor_name
    ) {
        if (!$this->serviceApiUrl) {
            return false;
        }

        $endpoint = $this->getEndpoint('/sender/vouchers/provider_approved/');

        $res = $this->apiRequest->post($endpoint, [
            'reffer_id'     => $identifier,
            'fund_name'     => $fund_name,
            'provider_name' => $provider_name,
            'sponsor_name'  => $sponsor_name,
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
     * @param string $identifier
     * @param string $fund_name
     * @param string $provider_name
     * @param string $sponsor_name
     * @return bool
     */
    public function providerRejected(
        string $identifier,
        string $fund_name,
        string $provider_name,
        string $sponsor_name
    ) {
        if (!$this->serviceApiUrl) {
            return false;
        }

        $endpoint = $this->getEndpoint('/sender/vouchers/provider_rejected/');

        $res = $this->apiRequest->post($endpoint, [
            'reffer_id'     => $identifier,
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
     * @param string $identifier
     * @param string $sponsor_name
     * @return bool
     */
    public function youAddedAsValidator(
        string $identifier,
        string $sponsor_name
    ) {
        if (!$this->serviceApiUrl) {
            return false;
        }

        $endpoint = $this->getEndpoint('/sender/sponsors/you_added_as_validator/');

        $res = $this->apiRequest->post($endpoint, [
            'reffer_id' => $identifier,
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
     * @param string $identifier
     * @param string $validator_dashboard_link
     * @return bool
     */
    public function newValidationRequest(
        string $identifier,
        string $validator_dashboard_link
    ) {
        if (!$this->serviceApiUrl) {
            return false;
        }

        $endpoint = $this->getEndpoint('/sender/validations/new_validation_request/');

        $res = $this->apiRequest->post($endpoint, [
            'reffer_id'                 => $identifier,
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
     * @param string $identifier
     * @param string $fund_name
     * @param string $provider_dashboard_link
     * @return bool
     */
    public function newFundApplicable(
        string $identifier,
        string $fund_name,
        string $provider_dashboard_link
    ) {
        if (!$this->serviceApiUrl) {
            return false;
        }

        $endpoint = $this->getEndpoint('/sender/vouchers/new_fund/');

        $res = $this->apiRequest->post($endpoint, [
            'reffer_id'                 => $identifier,
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
     * @param string $identifier
     * @param string $fund_name
     * @param string $webshop_link
     * @return bool
     */
    public function newFundCreated(
        string $identifier,
        string $fund_name,
        string $webshop_link
    ) {
        if (!$this->serviceApiUrl) {
            return false;
        }

        $endpoint = $this->getEndpoint('/sender/vouchers/new_fund_created/');

        $res = $this->apiRequest->post($endpoint, [
            'reffer_id'     => $identifier,
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
     * Notify user that new product added
     * @param string $identifier
     * @param string $sponsor_name
     * @param string $fund_name
     * @return bool
     */
    public function newProductAdded(
        string $identifier,
        string $sponsor_name,
        string $fund_name
    ) {
        if (!$this->serviceApiUrl) {
            return false;
        }

        $endpoint = $this->getEndpoint('/sender/vouchers/new_product_added/');

        $res = $this->apiRequest->post($endpoint, [
            'reffer_id'     => $identifier,
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
     * @param string $identifier
     * @param string $fund_product_name
     * @param string $qr_url
     * @return bool
     */
    public function sendVoucher(
        string $identifier,
        string $fund_product_name,
        string $qr_url
    ) {
        if (!$this->serviceApiUrl) {
            return false;
        }

        $endpoint = $this->getEndpoint('/sender/vouchers/sended_via_email/');

        $res = $this->apiRequest->post($endpoint, [
            'reffer_id'             => $identifier,
            'fund_product_name'     => $fund_product_name,
            'qr_url'                => $qr_url,
        ]);

        if ($res->getStatusCode() != 200) {
            app()->make('log')->error(
                sprintf(
                    'Error sending notification `sendVoucher`: %s',
                    $res->getBody()
                )
            );

            return false;
        }

        return true;
    }

    /**
     * Send restore identity link to address email
     * @param string $identifier
     * @param string $link
     * @param string $platform
     * @return bool
     */
    public function loginViaEmail(
        string $identifier,
        string $link,
        string $platform
    ) {
        if (!$this->serviceApiUrl) {
            return false;
        }

        $endpoint = $this->getEndpoint('/sender/login/login_via_email/');

        $res = $this->apiRequest->post($endpoint, [
            'reffer_id' => $identifier,
            'link'      => $link,
            'platform'  => $platform,
        ]);

        if ($res->getStatusCode() != 200) {
            app()->make('log')->error(
                sprintf(
                    'Error sending notification `loginViaEmail`: %s',
                    $res->getBody()
                )
            );

            return false;
        }

        return true;
    }
}