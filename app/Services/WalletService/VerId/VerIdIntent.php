<?php

namespace App\Services\WalletService\VerId;

use Illuminate\Support\Facades\Http;
use Throwable;

class VerIdIntent
{
    /**
     * @param array $config
     * @param string $codeChallenge
     * @param mixed $intentEndpoint
     * @param string $brandUuid
     * @param string $scope
     * @param string|null $challenge
     */
    public function __construct(
        protected array $config,
        protected string $codeChallenge,
        protected mixed $intentEndpoint,
        protected string $brandUuid,
        protected string $scope = 'openid',
        protected ?string $challenge = null,
    ) {
    }

    /**
     * @return VerIdIntentResponse
     */
    public function send(): VerIdIntentResponse
    {
        $brandUuid = $this->brandUuid();
        $intentEndpoint = $this->endpoint();

        $clientId = trim((string) ($this->config['client_id'] ?? ''));
        $clientSecret = (string) ($this->config['client_secret'] ?? '');

        if (!$intentEndpoint) {
            return VerIdIntentResponse::failed(
                VerIdIntentResponse::ERROR_MISSING_INTENT_ENDPOINT,
                VerIdIntentResponse::MESSAGE_MISSING_INTENT_ENDPOINT,
            );
        }

        if ($clientId === '' || trim($clientSecret) === '' ||
            !in_array($this->scope, ['openid', 'disclosure'], true) ||
            ($brandUuid === '' && $this->challenge === null)) {
            return VerIdIntentResponse::failed(
                VerIdIntentResponse::ERROR_INVALID_INTENT_CONFIG,
                VerIdIntentResponse::MESSAGE_INVALID_INTENT_CONFIG,
            );
        }

        $payload = [
            'scope' => $this->scope,
            'client_id' => $clientId,
            'code_challenge' => $this->codeChallenge,
            ...($brandUuid !== '' ? ['brandUuid' => $brandUuid] : []),
            ...($this->challenge !== null ? ['challenge' => $this->challenge] : []),
        ];

        try {
            return VerIdIntentResponse::fromHttpResponse(Http::asJson()
                ->acceptJson()
                ->withBasicAuth($clientId, $clientSecret)
                ->timeout(10)
                ->post($intentEndpoint, $payload));
        } catch (Throwable $exception) {
            return VerIdIntentResponse::failed(
                VerIdIntentResponse::ERROR_REQUEST_EXCEPTION,
                VerIdIntentResponse::MESSAGE_REQUEST_EXCEPTION,
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
