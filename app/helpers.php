<?php

use \Carbon\Carbon;

if  (!function_exists('format_date')) {
    function format_date($value, string $format) {
        return (new Carbon($value))->format(
            config("forus.formats.$format") ?: $format
        );
    }
}

if  (!function_exists('format_date_locale')) {
    function format_date_locale($value, string $format) {
        return (new Carbon($value))->formatLocalized(
            config("forus.formats.$format") ?: $format
        );
    }
}