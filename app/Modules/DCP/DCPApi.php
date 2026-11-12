<?php

namespace App\Modules\DCP;

use App\Modules\DCP\Data\DCPOrderData;
use App\Modules\DCP\Data\DCPResponseData;
use App\Modules\DCP\Data\DCPStatusResult;
use App\Modules\DCP\Data\DCPSubmissionResult;
use App\Modules\DCP\Enums\DCPRemoteStatus;
use App\Modules\DCP\Enums\DCPResponseError;
use App\Modules\DCP\Enums\DCPSubmissionOutcome;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Throwable;

readonly class DCPApi
{
    /**
     * @param string $endpoint
     * @param string $username
     * @param string $password
     * @throws InvalidArgumentException
     */
    public function __construct(
        private string $endpoint,
        private string $username,
        private string $password,
    ) {
        if (parse_url($endpoint, PHP_URL_SCHEME) !== 'https' || !parse_url($endpoint, PHP_URL_HOST)) {
            throw new InvalidArgumentException('The DCP endpoint must use HTTPS.');
        }
    }

    /**
     * @param string $orderId
     * @param DCPOrderData $data
     * @return DCPSubmissionResult
     */
    public function createOrder(string $orderId, DCPOrderData $data): DCPSubmissionResult
    {
        try {
            $response = $this->http()->post('/api/Orders', $data->toArray($orderId));
        } catch (ConnectionException) {
            return new DCPSubmissionResult(
                DCPSubmissionOutcome::UNKNOWN,
                new DCPResponseData(),
                DCPResponseError::TRANSPORT,
            );
        }

        $parsed = $this->parseResponse($response);

        if ($parsed->orderId !== null && $parsed->orderId !== $orderId) {
            return new DCPSubmissionResult(
                DCPSubmissionOutcome::UNKNOWN,
                $parsed,
                DCPResponseError::REFERENCE_MISMATCH,
            );
        }

        if ($response->status() === 201 && $parsed->orderId === $orderId && $parsed->remoteId !== null &&
            $parsed->statusId !== DCPRemoteStatus::NOT_FOUND->value) {
            return new DCPSubmissionResult(DCPSubmissionOutcome::ACCEPTED, $parsed);
        }

        $duplicate = collect([$parsed->message, ...$parsed->errors])->filter()->contains(
            function (string $error): bool {
                return Str::contains($error, 'duplicate', true) ||
                    (Str::contains($error, 'already', true) && Str::contains($error, ['exist', 'registered'], true));
            },
        );

        if ($duplicate || $response->status() === 409) {
            return new DCPSubmissionResult(DCPSubmissionOutcome::UNKNOWN, $parsed, DCPResponseError::DUPLICATE);
        }

        if ($parsed->remoteId === null && (in_array($response->status(), [401, 403], true) ||
            (in_array($response->status(), [400, 422], true) && $parsed->errors !== []))) {
            return new DCPSubmissionResult(DCPSubmissionOutcome::REJECTED, $parsed, DCPResponseError::REJECTED);
        }

        return new DCPSubmissionResult(
            DCPSubmissionOutcome::UNKNOWN,
            $parsed,
            $response->successful() ? DCPResponseError::INVALID_RESPONSE : DCPResponseError::HTTP,
        );
    }

    /**
     * @param string $orderId
     * @param string $remoteId
     * @return DCPStatusResult
     */
    public function getOrder(string $orderId, string $remoteId): DCPStatusResult
    {
        try {
            $response = $this->http()->get('/api/Orders/' . rawurlencode($remoteId));
        } catch (ConnectionException) {
            return new DCPStatusResult(new DCPResponseData(), DCPResponseError::TRANSPORT);
        }

        $parsed = $this->parseResponse($response);

        if ($response->status() === 404) {
            return new DCPStatusResult($parsed, DCPResponseError::NOT_FOUND);
        }

        if ($response->status() !== 200) {
            return new DCPStatusResult($parsed, DCPResponseError::HTTP);
        }

        if ($parsed->orderId !== $orderId || $parsed->remoteId !== $remoteId) {
            return new DCPStatusResult($parsed, DCPResponseError::REFERENCE_MISMATCH);
        }

        return new DCPStatusResult($parsed, match ($parsed->statusId) {
            null => DCPResponseError::INVALID_RESPONSE,
            DCPRemoteStatus::NOT_FOUND->value => DCPResponseError::NOT_FOUND,
            default => null,
        });
    }

    /**
     * @return PendingRequest
     */
    private function http(): PendingRequest
    {
        return Http::baseUrl(rtrim($this->endpoint, '/'))
            ->withBasicAuth($this->username, $this->password)
            ->acceptJson()->asJson()->withoutRedirecting()
            ->connectTimeout(Config::get('dcp.connect_timeout'))
            ->timeout(Config::get('dcp.timeout'));
    }

    /**
     * @param Response $response
     * @return DCPResponseData
     */
    private function parseResponse(Response $response): DCPResponseData
    {
        $body = $response->json();
        $body = is_array($body) ? $body : null;
        $status = $body['statusId'] ?? null;
        $status = is_int($status) || is_string($status) ? filter_var($status, FILTER_VALIDATE_INT) : false;
        $remoteId = $this->stringValue($body['orderIdDCP'] ?? null);

        return new DCPResponseData(
            httpStatus: $response->status(),
            orderId: $this->stringValue($body['orderId'] ?? null),
            remoteId: $remoteId !== null && strlen($remoteId) <= 100 ? $remoteId : null,
            statusId: $status !== false ? $status : null,
            message: $this->stringValue($body['statusMessage'] ?? $body['messageStatus'] ?? null),
            shippedAt: $this->shippingDate($body['shippingDate'] ?? null),
            correlationId: $this->stringValue($body['correlationId'] ?? null),
            errors: $this->validationErrors($body['validationErrors'] ?? null),
            body: $body,
        );
    }

    /**
     * @param mixed $value
     * @return string|null
     */
    private function stringValue(mixed $value): ?string
    {
        return is_string($value) && trim($value) !== '' ? $value : null;
    }

    /**
     * @param mixed $errors
     * @return string[]
     */
    private function validationErrors(mixed $errors): array
    {
        $messages = [];

        foreach (is_array($errors) ? $errors : [] as $field => $values) {
            foreach (Arr::flatten((array) $values) as $value) {
                if (is_string($value) && $value !== '') {
                    $messages[] = is_string($field) ? $field . ': ' . $value : $value;
                }
            }
        }

        return $messages;
    }

    /**
     * @param mixed $value
     * @return CarbonImmutable|null
     */
    private function shippingDate(mixed $value): ?CarbonImmutable
    {
        if (!is_string($value) || !preg_match('/^\d{4}-\d{2}-\d{2}(?:T.*)?$/', $value)) {
            return null;
        }

        try {
            $parsed = date_parse($value);

            if ($parsed['warning_count'] || $parsed['error_count'] || isset($parsed['relative'])) {
                return null;
            }

            return CarbonImmutable::parse($value, 'UTC')->setTimezone(Config::get('app.timezone'));
        } catch (Throwable) {
            return null;
        }
    }
}
