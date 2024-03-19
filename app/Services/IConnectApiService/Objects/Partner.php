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
     * @return (mixed|string)[]
     *
     * @psalm-return array{country: ''|mixed, place: ''|mixed}
     */
    public function getPlaceStartMarriagePartnership(): array
    {
        return [
            'country' => $this->data['aangaanHuwelijkPartnerschap']['land']['omschrijving'] ?? '',
            'place' => $this->data['aangaanHuwelijkPartnerschap']['plaats']['omschrijving'] ?? '',
        ];
    }

    /**
     * @return ((mixed|string)[]|int|string)[]
     *
     * @psalm-return array{index: int, type_of_commitment: string, date_start_marriage_partnership: string, place_start_marriage_partnership: array<mixed|string>}
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
