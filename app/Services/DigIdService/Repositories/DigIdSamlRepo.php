<?php

namespace App\Services\DigIdService\Repositories;

use App\Services\DigIdService\DigIdException;
use App\Services\DigIdService\Objects\ClientTls;
use App\Services\DigIdService\Objects\DigidAuthRequestData;
use App\Services\DigIdService\Objects\DigidAuthResolveData;
use App\Services\DigIdService\Repositories\Interfaces\DigIdRepo;
use App\Services\SAML2Service\Exceptions\Saml2Exception;
use App\Services\SAML2Service\SamlAuth;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use OneLogin\Saml2\Error;

class DigIdSamlRepo extends DigIdRepo
{
    public const DIGID_CANCELLED = '0040';
    public const DIGID_STATUS_CANCELLED = 'AuthnFailed';
    protected array $configs;

    /**
     * @param array $configs
     */
    public function __construct(array $configs)
    {
        $this->configs = $configs;
    }

    /**
     * @param string $redirectUrl
     * @param string $sessionSecret
     * @param ClientTls|null $tlsCert
     * @return DigidAuthRequestData
     * @throws Saml2Exception
     * @throws Error
     */
    public function makeAuthRequest(
        string $redirectUrl,
        string $sessionSecret,
        ?ClientTls $tlsCert = null,
    ): DigidAuthRequestData {
        $auth = SamlAuth::make($this->makeSamlConfig([
            'sp.assertionConsumerService.url' => $redirectUrl,
        ]));

        $authRedirectUrl = $auth->login(null, [], true, false, true);

        return (new DigidAuthRequestData)
            ->setRequestId($auth->getLastRequestID())
            ->setAuthResolveUrl($redirectUrl)
            ->setAuthRedirectUrl($authRedirectUrl);
    }

    /**
     * @param Request $request
     * @param string $requestId
     * @param string $sessionSecret
     * @param ClientTls|null $tlsCert
     * @return DigidAuthResolveData
     * @throws DigIdException
     * @throws Saml2Exception
     * @throws \Throwable
     */
    public function resolveResponse(
        Request $request,
        string $requestId,
        string $sessionSecret,
        ?ClientTls $tlsCert = null,
    ): DigidAuthResolveData {
        $auth = SamlAuth::make($this->makeSamlConfig());
        $response = $auth->resolveArtifact($request->get('SAMLart'));

        if ($response->getInResponseTo() !== $requestId) {
            throw $this->makeException("DigiD: invalid response.", 'unknown_error');
        }

        if (!$response->isSuccess() && $response->getStatusSubCode() == $this::DIGID_STATUS_CANCELLED) {
            throw $this->makeException("Digid API Request canceled.", self::DIGID_CANCELLED);
        }

        if (!$response->isSuccess()) {
            throw $this->makeException("Digid invalid verify credentials request, response body.", 403);
        }

        return new DigidAuthResolveData(explode(':', $response->getUser()->getNameId())[1]);
    }

    /**
     * @param Request $request
     * @param string $session_secret
     * @return bool
     */
    public function validateResolveResponse(Request $request, string $session_secret): bool
    {
        return $request->get('session_secret') !== $session_secret;
    }

    /**
     * @param array $replace
     * @return array
     */
    protected function makeSamlConfig(array $replace = []): array
    {
        $configs = array_replace_recursive($this->configs, Config::get('saml'));

        foreach ($replace as $key => $value) {
            Arr::set($configs, $key, $value);
        }

        return $configs;
    }
}
