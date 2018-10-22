<?php

namespace App\Services\Forus\MailNotification;

use App\Services\ApiRequestService\ApiRequest;

class MailService
{
    const TYPE_EMAIL = 1;
    const TYPE_PUSH_MESSAGE = 2;

    protected $serviceApiUrl;

    /** @var ApiRequest $apiRequest  */
    protected $apiRequest;

    public function __construct() {
        $this->apiRequest = app()->make('api_request');
        $this->serviceApiUrl = env('SERVICE_EMAIL_URL', false);
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
     * @param $userId
     * @param $type
     * @param $value
     * @return bool
     */
    public function addConnection($userId, $type, $value)
    {
        if (!$this->serviceApiUrl) {
            return false;
        }

        $res = $this->apiRequest->post($this->serviceApiUrl . '/en/user/connections/add/', [
            'user_id'   => $userId,
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

    public function providerApproved(
        $reffer_id, $fund_name, $provider_name, $sponsor_name, $date_start
    ) {
        if (!$this->serviceApiUrl) {
            return false;
        }

        $res = $this->apiRequest->post($this->serviceApiUrl . '/en/sender/vouchers/provider_approved', compact(
            'reffer_id', 'fund_name', 'provider_name', 'sponsor_name', 'date_start'
        ));

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

    public function providerApplied(
        $reffer_id, $fund_name, $provider_name, $sponsor_name
    ) {
        if (!$this->serviceApiUrl) {
            return false;
        }

        $res = $this->apiRequest->post($this->serviceApiUrl . '/en/sender/vouchers/provider_applied', compact(
            'reffer_id', 'fund_name', 'provider_name', 'sponsor_name'
        ));

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

    public function youAddedAsValidator(
        $reffer_id, $sponsore_name, $validator_name
    ) {
        if (!$this->serviceApiUrl) {
            return false;
        }

        $res = $this->apiRequest->post($this->serviceApiUrl . '/en/sender/sponsors/you_added_as_validator', compact(
            'reffer_id', 'sponsore_name', 'validator_name'
        ));

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

    public function newValidationRequest(
        $reffer_id, $validator_name
    ) {
        if (!$this->serviceApiUrl) {
            return false;
        }

        $res = $this->apiRequest->post($this->serviceApiUrl . '/en/sender/validations/new_validation_request', compact(
            'reffer_id', 'validator_name'
        ));

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

    public function newFundApplicable(
        $reffer_id, $fund_name, $username
    ) {
        if (!$this->serviceApiUrl) {
            return false;
        }

        $res = $this->apiRequest->post($this->serviceApiUrl . '/en/sender/vouchers/new_fund', compact(
            'reffer_id', 'fund_name', 'username'
        ));

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

    public function newFundCreated(
        $reffer_id, $fund_name, $requester_name, $link
    ) {
        if (!$this->serviceApiUrl) {
            return false;
        }

        $res = $this->apiRequest->post($this->serviceApiUrl . '/en/sender/vouchers/new_fund_created', compact(
            'reffer_id', 'fund_name', 'requester_name', 'link'
        ));

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

    public function newProductAdded(
        $reffer_id, $product_name, $provider_name
    ) {
        if (!$this->serviceApiUrl) {
            return false;
        }

        $res = $this->apiRequest->post($this->serviceApiUrl . '/en/sender/vouchers/new_product_added', compact(
            'reffer_id', 'product_name', 'provider_name'
        ));

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
}