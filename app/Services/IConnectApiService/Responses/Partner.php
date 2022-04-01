<?php

namespace App\Services\IConnectApiService\Responses;

/**
 * Class ParentPerson
 * @package App\Services\IConnectApiService\Responses
 */
class Partner extends BasePerson
{
    /**
     * @return string
     */
    public function getTypeOfCommitment(): string
    {
        return $this->raw['soortVerbintenis'] ?? '';
    }

    /**
     * @return string
     */
    public function getDateStartMarriagePartnership(): string
    {
        return $this->raw['aangaanHuwelijkPartnerschap']['datum']['datum'] ?? '';
    }

    /**
     * @return array|string[]
     */
    public function getPlaceStartMarriagePartnership(): array
    {
        return [
            'country' => $this->raw['aangaanHuwelijkPartnerschap']['land']['omschrijving'] ?? '',
            'place' => $this->raw['aangaanHuwelijkPartnerschap']['plaats']['omschrijving'] ?? '',
        ];
    }

    /**
     * @return array
     */
    public function getCustomDataArray(): array
    {
        return [
            'type_of_commitment' => $this->getTypeOfCommitment(),
            'date_start_marriage_partnership' => $this->getDateStartMarriagePartnership(),
            'place_start_marriage_partnership' => $this->getPlaceStartMarriagePartnership(),
        ];
    }
}