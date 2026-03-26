<?php

namespace App\Helpers;

class Number extends \Illuminate\Support\Number
{
    /**
     * @param float $amount
     * @return int
     */
    public static function toCents(float $amount): int
    {
        return (int) round($amount * 100);
    }
}
