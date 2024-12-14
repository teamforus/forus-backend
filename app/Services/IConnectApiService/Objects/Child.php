<?php

namespace App\Services\IConnectApiService\Objects;

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
