<?php

namespace App\Services\MollieService\Objects;

use Carbon\Carbon;

class Refund extends BaseObject
{
    /**
     * @var string
     */
    public string $id;

    /**
     * @var float
     */
    public float $amount;

    /**
     * @var string
     */
    public string $currency;

    /**
     * @var Carbon|null
     */
    public ?Carbon $created_at = null;

    /**
     * @var string|null
     */
    public ?string $description = null;

    /**
     * @var string
     */
    public string $payment_id;

    /**
     * @var string
     */
    public string $status;
}
