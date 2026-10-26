<?php

namespace App\Services\DigIdService\Repositories;

use App\Services\DigIdService\DigIdException;
use App\Services\DigIdService\Objects\DigidAuthRequestData;
use App\Services\DigIdService\Objects\DigidAuthResolveData;
use App\Services\DigIdService\Objects\DigIdResolveContext;
use App\Services\DigIdService\Objects\DigIdStartContext;
use App\Services\DigIdService\Traits\BuildsSamlConfig;
use App\Services\SAML2Service\Exceptions\Saml2Exception;
use App\Services\SAML2Service\Responses\SamlArtifactResponse;
use App\Services\SAML2Service\SamlAuth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use InvalidArgumentException;
use OneLogin\Saml2\Error;
use Throwable;

class DigIdSamlRepo extends BaseDigIdSamlRepo
{
    use BuildsSamlConfig;

    /**
     * @param DigIdStartContext $context
     * @throws Saml2Exception
     * @throws Error
     * @return DigidAuthRequestData
     */
    public function makeAuthRequest(DigIdStartContext $context): DigidAuthRequestData
    {
        $auth = SamlAuth::make($this->makeSamlConfig([
            'sp.assertionConsumerService.url' => $context->callbackUrl,
        ]));

        $authRedirectUrl = $auth->login(null, [], true, false, true);

        return (new DigidAuthRequestData())
            ->setRequestId($auth->getLastRequestID())
            ->setAuthResolveUrl($context->callbackUrl)
            ->setAuthRedirectUrl($authRedirectUrl);
    }

    /**
     * @param Request|SamlArtifactResponse $response
     * @param DigIdResolveContext $context
     * @throws DigIdException
     * @throws Saml2Exception
     * @throws Throwable
     * @return DigidAuthResolveData
     */
    public function resolveResponse(
        Request|SamlArtifactResponse $response,
        DigIdResolveContext $context,
    ): DigidAuthResolveData {
        if (!$response instanceof Request) {
            throw new InvalidArgumentException('Direct SAML resolution requires an HTTP request.');
        }

        $auth = SamlAuth::make($this->makeSamlConfig());
        $response = $auth->resolveArtifact($response->get('SAMLart'));

        $this->validateSamlResponse($response, $context->requestId);

        return new DigidAuthResolveData(explode(':', $response->getUser()->getNameId())[1]);
    }

    /**
     * @param array $replace
     * @return array
     */
    protected function makeSamlConfig(array $replace = []): array
    {
        return $this->mergeSamlConfig($this->configs, Config::get('saml'), $replace);
    }
}
