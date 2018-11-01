<?php

use \Carbon\Carbon;

if  (!function_exists('format_date')) {
    function format_date($value, string $format = 'short_date_time') {
        return (new Carbon($value))->format(
            config("forus.formats.$format") ?: $format
        );
    }
}

if  (!function_exists('format_date_locale')) {
    function format_date_locale($value, string $format = 'short_date_time_locale') {
        return (new Carbon($value))->formatLocalized(
            config("forus.formats.$format") ?: $format
        );
    }
}

if  (!function_exists('currency_format')) {
    function currency_format($number, $decimals = 2, $dec_point = '.', $thousands_sep = '') {
        return number_format($number, $decimals, $dec_point, $thousands_sep);
    }
}