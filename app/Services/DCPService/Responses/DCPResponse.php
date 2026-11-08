<?php

namespace App\Services\DCPService\Responses;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Throwable;

class DCPResponse
{
    /**
     * @param Response|null $response
     * @param Throwable|null $exception
     */
    public function __construct(
        protected ?Response $response = null,
        protected ?Throwable $exception = null,
    ) {
    }

    /**
     * @return bool
     */
    public function success(): bool
    {
        return (bool) $this->response?->successful();
    }

    /**
     * @return bool
     */
    public function rejected(): bool
    {
        return in_array($this->httpStatus(), [400, 422], true);
    }

    /**
     * DCP already has an order with the same client order id ("ReferenceClient"), eg: the previous attempt
     * timed out after DCP stored the order. DCP doesn't return the id of the existing order.
     *
     * @return bool
     */
    public function isDuplicateOrder(): bool
    {
        return $this->rejected() && collect($this->validationErrors())->contains(function ($error) {
            return Str::contains((string) $error, 'ReferenceClient') &&
                Str::contains((string) $error, 'already exist', true);
        });
    }

    /**
     * @return string[]
     */
    public function validationErrors(): array
    {
        return self::parseValidationErrors(Arr::get($this->body() ?: [], 'validationErrors'));
    }

    /**
     * @param mixed $errors
     * @return string[]
     */
    public static function parseValidationErrors(mixed $errors): array
    {
        $messages = [];

        foreach (is_array($errors) ? $errors : [] as $key => $value) {
            foreach (Arr::flatten((array) $value) as $message) {
                if (is_string($message) && $message !== '') {
                    $messages[] = is_string($key) ? "$key: $message" : $message;
                }
            }
        }

        return $messages;
    }

    /**
     * @return int|null
     */
    public function httpStatus(): ?int
    {
        return $this->response?->status();
    }

    /**
     * @return array|null
     */
    public function body(): ?array
    {
        $body = $this->response?->json();

        return is_array($body) ? $body : null;
    }

    /**
     * @return string|null
     */
    public function orderIdDcp(): ?string
    {
        $orderIdDcp = Arr::get($this->body() ?: [], 'orderIdDCP');

        return is_scalar($orderIdDcp) && (string) $orderIdDcp !== '' ? (string) $orderIdDcp : null;
    }

    /**
     * @return int|null
     */
    public function statusId(): ?int
    {
        $statusId = Arr::get($this->body() ?: [], 'statusId');

        return is_numeric($statusId) ? (int) $statusId : null;
    }

    /**
     * @return string|null
     */
    public function statusMessage(): ?string
    {
        if ($this->exception) {
            return $this->exception->getMessage();
        }

        $body = $this->body() ?: [];
        $message = Arr::get($body, 'statusMessage', Arr::get($body, 'messageStatus'));

        return implode(' ', array_filter([
            is_scalar($message) ? (string) $message : null,
            ...$this->validationErrors(),
        ])) ?: null;
    }

    /**
     * @return Carbon|null
     */
    public function shippingDate(): ?Carbon
    {
        $shippingDate = Arr::get($this->body() ?: [], 'shippingDate');

        try {
            return $shippingDate && is_string($shippingDate) ? Carbon::parse($shippingDate) : null;
        } catch (Throwable) {
            return null;
        }
    }
}
