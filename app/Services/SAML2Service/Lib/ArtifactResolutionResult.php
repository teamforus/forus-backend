<?php

namespace App\Services\SAML2Service\Lib;

use DOMElement;
use SAML2\Response;

readonly class ArtifactResolutionResult
{
    /**
     * @param Response $response
     * @param DOMElement $rawResponse
     */
    public function __construct(
        public Response $response,
        public DOMElement $rawResponse,
    ) {
    }
}
