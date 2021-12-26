<?php

namespace App\Services\IConnectApiService\Responses;

/**
 * Class ParentPerson
 * @package App\Services\IConnectApiService\Responses
 */
class ParentPerson extends BasePerson
{

    /**
     * @return string
     */
    public function getParentType(): string
    {
        return $this->raw['ouderAanduiding'] ?? '';
    }

    /**
     * @return string
     */
    public function getDateStartFamilyLawRelationship(): string
    {
        return $this->raw['datumIngangFamilierechtelijkeBetrekking']['datum'] ?? '';
    }

    /**
     * @return array
     */
    public function getCustomDataArray(): array
    {
        return [
            'parent_type' => $this->getParentType(),
            'date_start_family_law_relationship' => $this->getDateStartFamilyLawRelationship(),
        ];
    }
}
