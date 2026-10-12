<?php

namespace App\Services\OpenIdService\VerId;

use Illuminate\Http\Client\Response;
use Throwable;

class VerIdAuthenticationIntentResponse
{
    public const string ERROR_MISSING_INTENT_ENDPOINT = 'missing_intent_endpoint';
    public const string MESSAGE_MISSING_INTENT_ENDPOINT = 'Ver.id issuer metadata does not include intent_endpoint.';

    public const string ERROR_INVALID_INTENT_CONFIG = 'invalid_intent_config';
    public const string MESSAGE_INVALID_INTENT_CONFIG = 'Ver.id authentication intent config is incomplete.';

    public const string ERROR_HTTP_ERROR = 'http_error';
    public const string MESSAGE_HTTP_ERROR = 'Ver.id intent request failed.';

    public const string ERROR_REQUEST_EXCEPTION = 'request_exception';
    public const string MESSAGE_REQUEST_EXCEPTION = 'Ver.id authentication intent request threw an exception.';

    public const string ERROR_MISSING_INTENT_ID = 'missing_intent_id';
    public const string MESSAGE_MISSING_INTENT_ID = 'Ver.id intent response did not include intent_id.';

    /**
     * @param bool $successful
     * @param string|null $id
     * @param int|null $status
     * @param string|null $errorCode
     * @param string|null $errorMessage
     * @param array|null $response
     * @param Throwable|null $exception
     */
    public function __construct(
        protected bool $successful,
        protected ?string $id = null,
        protected ?int $status = null,
        protected ?string $errorCode = null,
        protected ?string $errorMessage = null,
        protected ?array $response = null,
        protected ?Throwable $exception = null,
    ) {
    }

    /**
     * @param Response $response
     * @return self
     */
    public static function fromHttpResponse(Response $response): self
    {
        $data = $response->json();
        $data = is_array($data) ? $data : [];
        $intentId = $data['intent_id'] ?? null;

        if ($response->successful() && is_string($intentId) && trim($intentId) !== '') {
            return new self(true, trim($intentId), $response->status(), response: $data);
        }

        return self::failed(
            errorCode: self::stringField($data, ['error_code', 'error']) ?: (
                $response->successful() ? self::ERROR_MISSING_INTENT_ID : self::ERROR_HTTP_ERROR
            ),
            errorMessage: self::stringField($data, ['error_description', 'error_message', 'message']) ?: (
                $response->successful()
                    ? self::MESSAGE_MISSING_INTENT_ID
                    : self::MESSAGE_HTTP_ERROR
            ),
            status: $response->status(),
            response: $data,
        );
    }

    /**
     * @param string|null $errorCode
     * @param string|null $errorMessage
     * @param int|null $status
     * @param array|null $response
     * @param Throwable|null $exception
     * @return self
     */
    public static function failed(
        ?string $errorCode = null,
        ?string $errorMessage = null,
        ?int $status = null,
        ?array $response = null,
        ?Throwable $exception = null,
    ): self {
        return new self(false, null, $status, $errorCode, $errorMessage, $response, $exception);
    }

    /**
     * @return bool
     */
    public function successful(): bool
    {
        return $this->successful && $this->id !== null;
    }

    /**
     * @return string|null
     */
    public function id(): ?string
    {
        return $this->id;
    }

    /**
     * @return int|null
     */
    public function status(): ?int
    {
        return $this->status;
    }

    /**
     * @return string|null
     */
    public function errorCode(): ?string
    {
        return $this->errorCode;
    }

    /**
     * @return string|null
     */
    public function errorMessage(): ?string
    {
        return $this->errorMessage;
    }

    /**
     * @return Throwable|null
     */
    public function exception(): ?Throwable
    {
        return $this->exception;
    }

    /**
     * @param bool $includeRawResponse
     * @param bool $includeExceptionMessages
     * @return array
     */
    public function logContext(bool $includeRawResponse = false, bool $includeExceptionMessages = false): array
    {
        return array_filter([
            'http_status' => $this->status,
            'error_code' => $this->errorCode,
            'error_message' => $this->errorMessage,
            'exception_class' => $this->exception ? get_class($this->exception) : null,
            ...($this->exception && $includeExceptionMessages ? [
                'exception_message' => $this->exception->getMessage(),
            ] : []),
            ...($includeRawResponse ? [
                'response' => $this->response,
            ] : []),
        ], static fn ($value) => $value !== null);
    }

    /**
     * @param array $data
     * @param array $keys
     * @return string|null
     */
    protected static function stringField(array $data, array $keys): ?string
    {
        foreach ($keys as $key) {
            $value = $data[$key] ?? null;

            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        }

        return null;
    }
}
