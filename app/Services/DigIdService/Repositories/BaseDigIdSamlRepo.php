<?php

namespace App\Services\DigIdService\Repositories;

use App\Services\DigIdService\DigIdException;
use App\Services\DigIdService\Repositories\Interfaces\DigIdRepo;
use App\Services\SAML2Service\Responses\SamlArtifactResponse;

abstract class BaseDigIdSamlRepo extends DigIdRepo
{
    public const string DIGID_STATUS_CANCELLED = 'AuthnFailed';

    protected array $configs;

    /**
     * @param array $configs
     */
    public function __construct(array $configs)
    {
        $this->configs = $configs;
    }

    /**
     * @param SamlArtifactResponse $response
     * @param string $requestId
     * @throws DigIdException
     * @return void
     */
    protected function validateSamlResponse(SamlArtifactResponse $response, string $requestId): void
    {
        if ($response->getInResponseTo() !== $requestId) {
            throw DigIdException::make('DigiD response does not match the authentication request.', 'unknown_error');
        }

        if ($response->isSuccess()) {
            return;
        }

        if ($response->getStatusSubCode() === self::DIGID_STATUS_CANCELLED) {
            throw DigIdException::make('DigiD authentication canceled.', self::ERROR_CANCELLED);
        }

        throw DigIdException::make('DigiD authentication failed.', '403');
    }
}
