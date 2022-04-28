<?php

namespace App\Services\IConnectApiService\Objects;

/**
 * Class ParentPerson
 * @package App\Services\IConnectApiService\Responses
 */
class Child extends BasePerson
{
    /**
     * @return array
     */
    public function getCustomDataArray(): array
    {
        return [
            'index' => $this->getIndex(),
        ];
    }
}
