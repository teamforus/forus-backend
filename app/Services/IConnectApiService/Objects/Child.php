<?php

namespace App\Services\IConnectApiService\Objects;

/**
 * Class ParentPerson
 * @package App\Services\IConnectApiService\Responses
 */
class Child extends BasePerson
{
    /**
     * @return int[]
     *
     * @psalm-return array{index: int}
     */
    public function getCustomDataArray(): array
    {
        return [
            'index' => $this->getIndex(),
        ];
    }
}
