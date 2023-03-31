<?php

namespace App\Services\DigIdService\Objects;

class DigidAuthResolveData extends DigidData
{
    protected int|string $uid;

    /**
     * @param string $uid
     * @param array $meta
     */
    public function __construct(string $uid, array $meta = [])
    {
        $this->uid = $uid;
        $this->meta = $meta;
    }

    /**
     * @return int|string
     */
    public function getUid(): int|string
    {
        return $this->uid;
    }
}