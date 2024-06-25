<?php

namespace App\Services\MollieService\Objects;


class Organization extends BaseObject
{
    /**
     * @var string
     */
    public string $id;

    /**
     * @var string
     */
    public string $name;

    /**
     * @var string|null
     */
    public ?string $email = null;

    /**
     * @var string|null
     */
    public ?string $locale = null;

    /**
     * @var string|null
     */
    public ?string $city = null;

    /**
     * @var string|null
     */
    public ?string $street = null;

    /**
     * @var string|null
     */
    public ?string $country = null;

    /**
     * @var string|null
     */
    public ?string $postcode = null;

    /**
     * @var string|null
     */
    public ?string $registration_number = null;

    /**
     * @var string|null
     */
    public ?string $vat_number = null;
}
