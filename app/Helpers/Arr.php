<?php


namespace App\Helpers;

class Arr extends \Illuminate\Support\Arr
{


    /**
     * @param (int|string)[] $array
     *
     * @return array
     *
     * @psalm-param list<array-key> $array
     */
    public static function duplicates(array $array): array
    {
        return array_filter($array, function($value, $key) use ($array) {
            return array_search($value, $array) != $key;
        }, ARRAY_FILTER_USE_BOTH);
    }
}
