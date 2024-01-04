<?php

namespace App\Services\MollieService\Objects;

use Carbon\Carbon;
use Mollie\Api\Types\PaymentStatus;

class Payment extends BaseObject
{
    /**
     * @var string
     */
    public string $id;

    /**
     * @var string
     */
    public string $profile_id;

    /**
     * @var float
     */
    public float $amount;

    /**
     * @var string
     */
    public string $currency;

    /**
     * @var float|null
     */
    public ?float $amount_refunded = null;

    /**
     * @var float|null
     */
    public ?float $amount_captured = null;

    /**
     * @var float|null
     */
    public ?float $amount_remaining = null;

    /**
     * @var string
     */
    public string $description;

    /**
     * @var string|null
     */
    public ?string $method = null;

    /**
     * @var string
     */
    public string $status;

    /**
     * @var Carbon|null
     */
    public ?Carbon $created_at = null;

    /**
     * @var Carbon|null
     */
    public ?Carbon $paid_at = null;

    /**
     * @var Carbon|null
     */
    public ?Carbon $canceled_at = null;

    /**
     * @var Carbon|null
     */
    public ?Carbon $expired_at = null;

    /**
     * @var Carbon|null
     */
    public ?Carbon $expires_at = null;

    /**
     * @var string|null
     */
    public ?string $checkout_url = null;

    /**
     * @return bool
     */
    public function isCanceled(): bool
    {
        return $this->status === PaymentStatus::STATUS_CANCELED;
    }

    /**
     * @return bool
     */
    public function isExpired(): bool
    {
        return $this->status === PaymentStatus::STATUS_EXPIRED;
    }

    /**
     * @return bool
     */
    public function isOpen(): bool
    {
        return $this->status === PaymentStatus::STATUS_OPEN;
    }

    /**
     * @return bool
     */
    public function isPending(): bool
    {
        return $this->status === PaymentStatus::STATUS_PENDING;
    }

    /**
     * @return bool
     */
    public function isAuthorized(): bool
    {
        return $this->status === PaymentStatus::STATUS_AUTHORIZED;
    }

    /**
     * @return bool
     */
    public function isFailed(): bool
    {
        return $this->status === PaymentStatus::STATUS_FAILED;
    }

    /**
     * @return bool
     */
    public function isPaid(): bool
    {
        return !empty($this->paid_at);
    }

    /**
     * @return string|null
     */
    public function getCheckoutUrl(): ?string
    {
        return $this->checkout_url;
    }
}
