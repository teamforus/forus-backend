<?php

use App\Models\Implementation;
use \Carbon\Carbon;
use \Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Support\Facades\Lang;

if  (!function_exists('format_date')) {
    /**
     * @param $value
     * @param string $format
     * @return string
     */
    function format_date($value, string $format = 'short_date_time') {
        return (new Carbon($value))->format(
            config("forus.formats.$format") ?: $format
        );
    }
}

if  (!function_exists('format_datetime_locale')) {
    /**
     * @param $value
     * @param string $format
     * @return string
     */
    function format_datetime_locale($value, string $format = 'short_date_time_locale') {
        return (new Carbon($value))->formatLocalized(
            config("forus.formats.$format") ?: $format
        );
    }
}

if  (!function_exists('format_date_locale')) {
    /**
     * @param $value
     * @param string $format
     * @return string
     */
    function format_date_locale($value, string $format = 'short_date_locale') {
        return (new Carbon($value))->formatLocalized(
            config("forus.formats.$format") ?: $format
        );
    }
}

if  (!function_exists('currency_format')) {
    /**
     * @param $number
     * @param int $decimals
     * @param string $dec_point
     * @param string $thousands_sep
     * @return string
     */
    function currency_format($number, $decimals = 2, $dec_point = '.', $thousands_sep = '') {
        return number_format($number, $decimals, $dec_point, $thousands_sep);
    }
}

if  (!function_exists('currency_format_locale')) {
    /**
     * @param $number
     * @return string
     */
    function currency_format_locale($number) {
        return ($number % 1 == 0 ? intval($number) : currency_format($number)) . ',-';
    }
}


if  (!function_exists('rule_number_format')) {
    /**
     * @param $number
     * @param int $decimals
     * @param string $dec_point
     * @param string $thousands_sep
     * @return string
     */
    function rule_number_format(
        $number,
        $decimals = 2,
        $dec_point = '.',
        $thousands_sep = ''
    ) {
        return number_format(
            floatval(is_numeric($number) ? $number : 0),
            $decimals,
            $dec_point,
            $thousands_sep
        );
    }
}


if  (!function_exists('authorize')) {
    /**
     * @param $ability
     * @param array $arguments
     * @return \Illuminate\Auth\Access\Response
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    function authorize($ability, $arguments = []) {
        $normalizeGuessedAbilityName = function ($ability) {
            $map = [
                'show' => 'view',
                'create' => 'create',
                'store' => 'create',
                'edit' => 'update',
                'update' => 'update',
                'destroy' => 'delete',
            ];

            return $map[$ability] ?? $ability;
        };

        $parseAbilityAndArguments = function ($ability, $arguments)  use ($normalizeGuessedAbilityName) {
            if (is_string($ability) && strpos($ability, '\\') === false) {
                return [$ability, $arguments];
            }

            $method = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3)[2]['function'];

            return [$normalizeGuessedAbilityName($method), $ability];
        };

        list($ability, $arguments) = $parseAbilityAndArguments($ability, $arguments);

        return app(Gate::class)->authorize($ability, $arguments);
    }
}

if (!function_exists('implementation_key')) {
    /**
     * @return array|string
     */
    function implementation_key() {
        return request()->header('Client-Key', false);
    }
}

if (!function_exists('flattenBy_key')) {
    /**
     * flattens a multi dimensional array into an associative array,
     * having the in-depth indexes as keys
     */
    function flatten_by_key(array $array, string $prefix = ''): array {
        $result = [];

        foreach($array as $key => $value) {
            if(is_array($value)) {
                $result = $result + flatten_by_key($value, $prefix . $key . '.');
            }
            else {
                $result[$prefix . $key] = $value;
            }
        }

        return $result;
    }
}

if (!function_exists('implementation_trans')) {
    /**
     * Returns translations based on the current implementation
     *
     * @param string $key
     * @param array $replace
     * @param string|null $locale
     * @return string|array|null
     */
    function implementation_trans(
        string $key,
        array $replace = [],
        string $locale = null
    ) {
        $implementation = Implementation::activeKey();

        if(Lang::has('mails.implementations.' . $implementation . '.' . $key)) {
            return Lang::get(
                'mails.implementations.' . $implementation . '.' . $key,
                $replace,
                $locale
            );
        }
        elseif(Lang::has('mails.implementations.general.' . $key)) {
            return Lang::get(
                'mails.implementations.general.' . $key,
                $replace,
                $locale
            );
        }

        return trans($key, $replace, $locale);
    }
}
