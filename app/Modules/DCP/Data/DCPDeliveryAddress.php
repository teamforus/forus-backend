<?php

namespace App\Modules\DCP\Data;

readonly class DCPDeliveryAddress
{
    public const string COUNTRY = 'NETHERLANDS';

    /**
     * @param string $firstName
     * @param string $lastName
     * @param string $address1
     * @param string $postalCode
     * @param string $city
     * @param string $country
     */
    public function __construct(
        public string $firstName,
        public string $lastName,
        public string $address1,
        public string $postalCode,
        public string $city,
        public string $country = self::COUNTRY,
    ) {
    }

    /**
     * @return array<string, string>
     */
    public function toArray(): array
    {
        return [
            'firstname' => $this->firstName,
            'lastname' => $this->lastName,
            'fullname' => trim($this->firstName . ' ' . $this->lastName),
            'address1' => $this->address1,
            'zipcode' => $this->postalCode,
            'city' => $this->city,
            'country' => $this->country,
        ];
    }
}
