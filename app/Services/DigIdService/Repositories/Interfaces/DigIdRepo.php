<?php

namespace App\Services\DigIdService\Repositories\Interfaces;

use App\Services\DigIdService\Objects\DigidAuthRequestData;
use App\Services\DigIdService\Objects\DigidAuthResolveData;
use App\Services\DigIdService\Objects\DigIdResolveContext;
use App\Services\DigIdService\Objects\DigIdStartContext;
use App\Services\SAML2Service\Responses\SamlArtifactResponse;
use Illuminate\Http\Request;
use Throwable;

abstract class DigIdRepo
{
    public const string ERROR_CANCELLED = 'cancelled';

    /**
     * @param DigIdStartContext $context
     * @throws Throwable
     * @return DigidAuthRequestData
     */
    abstract public function makeAuthRequest(DigIdStartContext $context): DigidAuthRequestData;

    /**
     * @param Request|SamlArtifactResponse $response
     * @param DigIdResolveContext $context
     * @throws Throwable
     * @return DigidAuthResolveData
     */
    abstract public function resolveResponse(
        Request|SamlArtifactResponse $response,
        DigIdResolveContext $context,
    ): DigidAuthResolveData;
}
