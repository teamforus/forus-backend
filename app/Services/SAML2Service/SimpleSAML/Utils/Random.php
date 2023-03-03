<?php

namespace SimpleSAML\Utils;

class Random
{
    /**
     * The fixed length of random identifiers.
     */
    public const ID_LENGTH = 43;

    /**
     * @return string
     */
    public function generateID(): string
    {
        return '_' . bin2hex(openssl_random_pseudo_bytes((self::ID_LENGTH - 1) / 2));
    }
}