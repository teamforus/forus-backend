<?php

namespace App\Services\MollieService\Objects;


class ResourceOwner extends BaseObject
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
    public ?string $first_name = null;

    /**
     * @var string|null
     */
    public ?string $last_name = null;

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
