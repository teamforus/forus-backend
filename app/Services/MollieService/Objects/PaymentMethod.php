<?php

namespace App\Services\MollieService\Objects;


class PaymentMethod extends BaseObject
{
    /**
     * @var string
     */
    public string $id;

    /**
     * @var string
     */
    public string $description;

    /**
     * @var string
     */
    public string $status;
}
