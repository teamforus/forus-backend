<?php

namespace App\Services\DigIdService\Repositories;

use App\Services\DigIdService\DigIdException;
use App\Services\DigIdService\Repositories\Interfaces\IDigIdRepo;

class DigIdRepo implements IDigIdRepo
{
    const DIGID_SUCCESS                     = '0000';
    const DIGID_UNAVAILABLE                 = '0001';
    const DIGID_TEMPORARY_UNAVAILABLE_1     = '0003';
    const DIGID_VERIFICATION_FAILED_1       = '0004';
    const DIGID_VERIFICATION_FAILED_2       = '0007';
    const DIGID_ILLEGAL_REQUEST             = '0030';
    const DIGID_ERROR_APP_ID                = '0032';
    const DIGID_ERROR_ASELECT               = '0033';
    const DIGID_CANCELLED                   = '0040';
    const DIGID_BUSY                        = '0050';
    const DIGID_INVALID_SESSION             = '0070';
    const DIGID_WEBSERVICE_NOT_ACTIVE       = '0080';
    const DIGID_WEBSERVICE_NOT_AUTHORISED   = '0099';
    const DIGID_TEMPORARY_UNAVAILABLE_2     = '010c';

    const URL_API_SANDBOX = "https://was-preprod1.digid.nl/was/server";
    // TODO: set real production api
    const URL_API_PRODUCTION = "https://was-preprod1.digid.nl/was/server";

    const ENV_SANDBOX = "sandbox";
    const ENV_PRODUCTION = "production";

    protected $app_id = null;
    protected $shared_secret = null;
    protected $a_select_server = null;
    protected $environment = self::ENV_SANDBOX;

    /**
     * DigIdRepo constructor.
     * @param string $env
     * @param string $app_id
     * @param string $shared_secret
     * @param string $a_select_server
     * @throws DigIdException
     */
    public function __construct(
        string $env = self::ENV_SANDBOX,
        string $app_id = "",
        string $shared_secret = "",
        string $a_select_server = ""
    ) {
        if (!in_array($env, [self::ENV_SANDBOX, self::ENV_PRODUCTION])) {
            throw new DigIdException("Invalid environment.");
        }

        $this->app_id = $app_id;
        $this->shared_secret = $shared_secret;
        $this->a_select_server = $a_select_server;
        $this->environment = $env;
    }

    /**
     * @return string
     * @throws DigIdException
     */
    private function getApiUrl() {
        if ($this->environment == self::ENV_SANDBOX) {
            return self::URL_API_SANDBOX;
        } elseif ($this->environment == self::ENV_PRODUCTION) {
            return self::URL_API_PRODUCTION;
        } else {
            throw new DigIdException("Invalid environment.");
        }
    }

    /**
     * @return array
     */
    private function getAuthParams() {
        return [
            "app_id"            => $this->app_id,
            "shared_secret"     => $this->shared_secret,
            "a-select-server"   => $this->a_select_server,
        ];
    }

    /**
     * @param array $params
     * @return array
     */
    private function makeAuthorizedRequest(array $params) {
        return array_merge($this->getAuthParams(), $params);
    }

    /**
     * @param array $params
     * @return string
     * @throws DigIdException
     */
    private function makeRequestUrl(array $params) {
        return sprintf("%s?%s", $this->getApiUrl(), http_build_query($params));
    }

    /**
     * @param string $app_url
     * @param array $extraParams
     * @return bool|string
     * @throws DigIdException
     */
    public function makeAuthRequestUrl($app_url = "", array $extraParams = [])
    {
        $result = [];

        $request = $this->makeAuthorizedRequest(array_merge([
            "request"           => "authenticate",
            "app_url"           => $app_url,
        ], $extraParams));

        //- exit(json_encode_pretty($request));

        parse_str(file_get_contents($this->makeRequestUrl($request)), $result);

        // exit(json_encode_pretty($result));

        // Validate result
        $check = TRUE;

        // Should be alphanumeric.
        $check = $check && isset($result['rid']) && ctype_alnum($result['rid']);

        // URL.
        $check = $check && isset($result['as_url']) && $result['as_url'];

        // Should be alphanumeric.
        $check = $check && isset($result['a-select-server']) && ctype_alnum($result['a-select-server']);

        // Should be alphanumeric.
        $check = $check && isset($result['result_code']) && ctype_digit($result['result_code']);

        // Should be numeric and 4 characters.
        if (isset($result['result_code']) && $result['result_code'] == self::DIGID_SUCCESS && $check) {
            // Build redirect URL.
            return sprintf('%s&%s', $result['as_url'],  http_build_query(array_merge([
                'rid'               => $result['rid'],
                'a-select-server'   => $result['a-select-server']
            ], $extraParams)));
        } else {
            return false;
        }
    }

    /**
     * @param string $rid
     * @param string $aselect_credentials
     * @return mixed
     * @throws DigIdException
     */
    public function getBsnFromResponse(
        string $rid,
        string $aselect_credentials
    ) {
        $result = [];

        $uri = $this->makeRequestUrl($this->makeAuthorizedRequest([
            'request'               => 'verify_credentials',
            'rid'                   => $rid,
            'aselect_credentials'   => $aselect_credentials,
        ]));

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $uri);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, TRUE);

        $request = curl_exec($ch);

        // Parse the response.
        if (!isset($request) || empty($request)) {
            throw new DigIdException("DigiD: has failed.");
        }

        parse_str($request, $result);

        // Validate result.
        $check = true;

        if (curl_getinfo($ch, CURLINFO_HTTP_CODE) !== 200) {
            throw new DigIdException("DigiD: invalid response.");
        }

        curl_close($ch);

        // Should be numeric.
        $check = $check && isset($result['uid']) && ctype_digit($result['uid']);
        // Should be alphanumeric.
        $check = $check && isset($result['app_id']) && ctype_alnum($result['app_id']);
        // Should be alphanumeric.
        $check = $check && isset($result['organization']) && ctype_alnum($result['organization']);
        // Should be numeric and 2 characters.
        $check = $check && isset($result['betrouwbaarheidsniveau']) && ctype_digit($result['betrouwbaarheidsniveau']);
        // Should be alphanumeric.
        $check = $check && isset($result['rid']) && ctype_alnum($result['rid']);
        // Should be alphanumeric.
        $check = $check && isset($result['a-select-server']) && ctype_alnum($result['a-select-server']);
        // Should be numeric and 4 characters.
        $check = $check && isset($result['result_code']) && ctype_digit($result['result_code']);

        if (!$check) {
            throw new DigIdException("Invalid response.");
        }

        if ($result['result_code'] == self::DIGID_SUCCESS && $check) {
            return $result['uid'];
        } elseif ($result['result_code'] == self::DIGID_CANCELLED) {
            throw new DigIdException('DigiD: login has canceled:');
        } else {
            throw new DigIdException('DigiD: login has failed:');
        }
    }
}