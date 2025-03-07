<?php

namespace App\Digests;

abstract class BaseDigest
{
    /**
     * @param array $data
     * @return array
     */
    public static function arrayOnlyString(array $data): array
    {
        return array_filter($data, fn ($value) => is_string($value) || is_numeric($value));
    }
}
