<?php

namespace App\Services\IConnectApiService\Responses;

/**
 * Class BasePerson
 * @package App\Services\IConnectApiService\Responses
 */
abstract class BasePerson
{
    /** @var array  */
    protected $raw;

    /**
     * @param array $data
     */
    public function __construct(array $data = []) {
        $this->raw = $data;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->getFirstName() . ' ' . $this->getLastName();
    }

    /**
     * @return string
     */
    public function getFirstName(): string
    {
        return $this->raw['naam']['voornamen'] ?? '';
    }

    /**
     * @return string
     */
    public function getLastName(): string
    {
        return $this->raw['naam']['geslachtsnaam'] ?? '';
    }

    /**
     * @return string
     */
    public function getBSN(): string
    {
        return $this->raw['burgerservicenummer'] ?? '';
    }

    /**
     * @return string
     */
    public function getGender(): string
    {
        return $this->raw['geslachtsaanduiding'] ?? '';
    }

    /**
     * @return string
     */
    public function getAge(): string
    {
        return $this->raw['leeftijd'] ?? '';
    }

    /**
     * @return string
     */
    public function getBirthdate(): string
    {
        return $this->raw['geboorte']['datum']['datum'] ?? '';
    }

    /**
     * @return array|string[]
     */
    public function getBirthplace(): array
    {
        return [
            'country' => $this->raw['geboorte']['land']['omschrijving'] ?? '',
            'place' => $this->raw['geboorte']['plaats']['omschrijving'] ?? ''
        ];
    }

    /**
     * @return array|string[]
     */
    public function toArray(): array
    {
        return array_merge([
            'name' => $this->getName(),
            'first_name' => $this->getFirstName(),
            'last_name' => $this->getLastName(),
            'age' => $this->getAge(),
            'birth_date' => $this->getBirthdate(),
            'birth_place' => $this->getBirthplace(),
            'bsn' => $this->getBSN(),
            'gender' => $this->getGender()
        ], $this->getCustomDataArray());
    }

    /**
     * @return array
     */
    abstract public function getCustomDataArray(): array;

}
