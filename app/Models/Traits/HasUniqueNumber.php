<?php

namespace App\Models\Traits;

use Throwable;

/**
 * @mixin \Eloquent
 */
trait HasUniqueNumber
{
    /**
     * @param string $column
     * @return string
     */
    public static function makeUniqueNumber(string $column = 'number'): string
    {
        do {
            try {
                $number = random_int(1000_0000, 9999_9999);
            } catch (Throwable) {
                return self::makeUniqueNumber();
            }
        } while (self::where($column, $number)->exists());

        return mb_str_pad($number, 8, '0', STR_PAD_LEFT);
    }
}