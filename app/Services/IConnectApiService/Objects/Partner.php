<?php

namespace App\Services\IConnectApiService\Objects;

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
        return $this->data['soortVerbintenis'] ?? '';
    }

    /**
     * @return string
     */
    public function getDateStartMarriagePartnership(): string
    {
        return $this->data['aangaanHuwelijkPartnerschap']['datum']['datum'] ?? '';
    }

    /**
     * @return array|string[]
     */
    public function getPlaceStartMarriagePartnership(): array
    {
        return [
            'country' => $this->data['aangaanHuwelijkPartnerschap']['land']['omschrijving'] ?? '',
            'place' => $this->data['aangaanHuwelijkPartnerschap']['plaats']['omschrijving'] ?? '',
        ];
    }

    /**
     * @return array
     */
    public function getCustomDataArray(): array
    {
        return [
            'index' => $this->getIndex(),
            'type_of_commitment' => $this->getTypeOfCommitment(),
            'date_start_marriage_partnership' => $this->getDateStartMarriagePartnership(),
            'place_start_marriage_partnership' => $this->getPlaceStartMarriagePartnership(),
        ];
    }
}
