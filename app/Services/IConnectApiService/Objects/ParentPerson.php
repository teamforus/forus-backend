<?php

namespace App\Services\IConnectApiService\Objects;

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
        return $this->data['ouderAanduiding'] ?? '';
    }

    /**
     * @return string
     */
    public function getDateStartFamilyLawRelationship(): string
    {
        return $this->data['datumIngangFamilierechtelijkeBetrekking']['datum'] ?? '';
    }

    /**
     * @return array
     */
    public function getCustomDataArray(): array
    {
        return [
            'index' => $this->getIndex(),
            'parent_type' => $this->getParentType(),
            'date_start_family_law_relationship' => $this->getDateStartFamilyLawRelationship(),
        ];
    }
}
