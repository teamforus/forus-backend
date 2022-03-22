<?php

namespace App\Services\BNGService\Responses\Consent;

use App\Services\BNGService\BNGService;
use App\Services\BNGService\Data\AuthData;
use App\Services\BNGService\Data\ResponseData;
use App\Services\BNGService\Responses\Value;

abstract class ConsentValue extends Value
{
    protected $redirectUri;
    protected $authData;

    /**
     * @param ResponseData $data
     * @param string $redirectToken
     * @param BNGService $BNGService
     */
    public function __construct(ResponseData $data, string $redirectToken, BNGService $BNGService)
    {
        parent::__construct($data);

        $this->redirectUri = $this->makeRedirectUri($BNGService->getAuthRedirectUrl(), $redirectToken);

        $this->authData = new AuthData(
            $BNGService->getEndpoint('authorise'),
            $this->getParams($BNGService)
        );
    }

    /**
     * @param BNGService $BNGService
     * @param array $params
     * @return array
     */
    protected function getParams(BNGService $BNGService, array $params = []): array
    {
        return array_merge([
            "scope" => $this->getScope(),
            "redirect_uri" => $this->getRedirectUri(),
            "response_type" => "code",
            "state" => $BNGService->makeToken(40),
            "code_challenge" => $BNGService->makeToken(40),
            "code_challenge_method" => "Plain",
            "client_id" => $BNGService->clientId(),
        ], $params);
    }

    /**
     * @return string
     */
    public function getRedirectUri(): string
    {
        return $this->redirectUri;
    }

    /**
     * @return AuthData
     */
    public function getAuthData(): AuthData
    {
        return $this->authData;
    }

    /**
     * @param string $authRedirectUri
     * @param string $redirectToken
     * @return string
     */
    abstract protected function makeRedirectUri(string $authRedirectUri, string $redirectToken): string;

    /**
     * @return mixed
     */
    abstract function getScope(): string;
}