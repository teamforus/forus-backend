<?php


namespace App\Helpers;

class Arr extends \Illuminate\Support\Arr
{
    /**
     * @param $array
     * @param callable $callback
     * @return array
     * @noinspection PhpUnused
     */
    public static function mapKeys($array, callable $callback): array
    {
        return array_combine(array_map($callback, array_keys($array)), array_values($array));
    }

    /**
     * @param $array
     * @param callable $callback
     * @return array
     * @noinspection PhpUnused
     */
    public static function whereKey($array, callable $callback): array
    {
        return array_only($array, self::where(array_keys($array), $callback));
    }

    /**
     * @param $array
     * @return array
     */
    public static function duplicates($array): array
    {
        return array_filter($array, function($value, $key) use ($array) {
            return array_search($value, $array) != $key;
        }, ARRAY_FILTER_USE_BOTH);
    }
}
