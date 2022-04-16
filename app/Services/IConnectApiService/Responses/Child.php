<?php

namespace App\Services\IConnectApiService\Responses;

/**
 * Class ParentPerson
 * @package App\Services\IConnectApiService\Responses
 */
class Child extends BasePerson
{
    /**
     * @return int
     */
    public function getIndex(): int
    {
        $array = explode("/", $this->raw['_links']['self']['href'] ?? '');
        return (int)end($array);
    }

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
