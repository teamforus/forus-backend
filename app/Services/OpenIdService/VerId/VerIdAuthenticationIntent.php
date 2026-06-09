<?php

namespace App\Services\OpenIdService\VerId;

use Illuminate\Support\Facades\Http;
use Throwable;

class VerIdAuthenticationIntent
{
    /**
     * @param array $config
     * @param string $codeChallenge
     * @param mixed $intentEndpoint
     * @param string $brandUuid
     */
    public function __construct(
        protected array $config,
        protected string $codeChallenge,
        protected mixed $intentEndpoint,
        protected string $brandUuid,
    ) {
    }

    /**
     * @return VerIdAuthenticationIntentResponse
     */
    public function send(): VerIdAuthenticationIntentResponse
    {
        $brandUuid = $this->brandUuid();
        $intentEndpoint = $this->endpoint();

        $clientId = trim((string) ($this->config['client_id'] ?? ''));
        $clientSecret = (string) ($this->config['client_secret'] ?? '');

        if (!$intentEndpoint) {
            return VerIdAuthenticationIntentResponse::failed(
                VerIdAuthenticationIntentResponse::ERROR_MISSING_INTENT_ENDPOINT,
                VerIdAuthenticationIntentResponse::MESSAGE_MISSING_INTENT_ENDPOINT,
            );
        }

        if ($clientId === '' || trim($clientSecret) === '' || $brandUuid === '') {
            return VerIdAuthenticationIntentResponse::failed(
                VerIdAuthenticationIntentResponse::ERROR_INVALID_INTENT_CONFIG,
                VerIdAuthenticationIntentResponse::MESSAGE_INVALID_INTENT_CONFIG,
            );
        }

        $payload = [
            'scope' => 'openid',
            'client_id' => $clientId,
            'code_challenge' => $this->codeChallenge,
            'brandUuid' => $brandUuid,
        ];

        try {
            return VerIdAuthenticationIntentResponse::fromHttpResponse(Http::asJson()
                ->acceptJson()
                ->withBasicAuth($clientId, $clientSecret)
                ->timeout(10)
                ->post($intentEndpoint, $payload));
        } catch (Throwable $exception) {
            return VerIdAuthenticationIntentResponse::failed(
                VerIdAuthenticationIntentResponse::ERROR_REQUEST_EXCEPTION,
                VerIdAuthenticationIntentResponse::MESSAGE_REQUEST_EXCEPTION,
                exception: $exception,
            );
        }
    }

    /**
     * @return string|null
     */
    public function endpoint(): ?string
    {
        return is_string($this->intentEndpoint) && trim($this->intentEndpoint) !== ''
            ? trim($this->intentEndpoint)
            : null;
    }

    /**
     * @return string
     */
    public function brandUuid(): string
    {
        return trim($this->brandUuid);
    }
}
