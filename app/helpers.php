<?php

use App\Models\Implementation;
use \Carbon\Carbon;
use \Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Support\Facades\Lang;

if (!function_exists('auth_user')) {
    /**
     * Get the available user instance.
     *
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    function auth_user()
    {
        return auth()->user();
    }
}

if (!function_exists('auth_address')) {
    /**
     * Get the available user instance.
     *
     * @return string|null
     */
    function auth_address()
    {
        return auth()->user() ? auth()->user()->getAuthIdentifier() : null;
    }
}

if (!function_exists('media')) {
    /**
     * @return \App\Services\MediaService\MediaService|mixed
     */
    function media()
    {
        return resolve('media');
    }
}

if (!function_exists('format_date')) {
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

if (!function_exists('format_datetime_locale')) {
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

if (!function_exists('format_date_locale')) {
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

if (!function_exists('currency_format')) {
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

if (!function_exists('currency_format_locale')) {
    /**
     * @param $number
     * @return string
     */
    function currency_format_locale($number) {
        return ($number % 1 == 0 ? intval($number) : currency_format($number)) . ',-';
    }
}


if (!function_exists('rule_number_format')) {
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


if (!function_exists('authorize')) {
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

if (!function_exists('mail_trans')) {
    /**
     * Returns translations based on the current implementation
     *
     * @param string $key
     * @param array $replace
     * @param string|null $locale
     * @return string|array|null
     */
    function mail_trans(
        string $key,
        array $replace = [],
        string $locale = null
    ) {
        $implementation = Implementation::activeKey();

        switch ($implementation) {
            case (Lang::has('mails/implementations/' . $implementation . '/' . $key)):
                return Lang::get(
                    'mails/implementations/' . $implementation . '/' . $key,
                    $replace,
                    $locale
                );
            case (Lang::has('mails/implementations/general/' . $key)):
                return Lang::get(
                    'mails/implementations/general/' . $key,
                    $replace,
                    $locale
                );
            case (Lang::has('mails/implementations/general.' . $key)):
                return Lang::get(
                    'mails/implementations/general.' . $key,
                    $replace,
                    $locale
                );
        }

        return trans($key, $replace, $locale);
    }
}

if (!function_exists('mail_config')) {
    /**
     * Returns mail configs based on the current implementation
     *
     * @param string $key
     * @param string|null $default
     * @param string|null $implementation
     * @return mixed|string
     */
    function mail_config(
        string $key,
        string $default = null,
        string $implementation = null
    ) {
        $implementation = $implementation ?: Implementation::activeKey();
        $configKey = "forus.mails.implementations.%s.$key";

        if (config()->has(sprintf($configKey, $implementation))) {
            return config()->get(sprintf($configKey, $implementation));
        } elseif (config()->has(sprintf($configKey, 'general'))) {
            return config()->get(sprintf($configKey, 'general'));
        }

        return $default ?: $key;
    }
}

if (!function_exists('str_terminal_color')) {
    function str_terminal_color(
        string $text,
        string $color = 'green'
    ) {
        $colors = [
            'black' => '30',
            'blue' => '34',
            'green' => '32',
            'cyan' => '36',
            'red' => '31',
            'purple' => '35',
            'brown' => '33',
            'light_gray' => '37',
            'dark_gray' => '30',
            'light_blue' => '34',
            'light_green' => '32',
            'light_cyan' => '36',
            'light_red' => '31',
            'light_purple' => '35',
            'yellow' => '33',
            'white' => '37',
        ];

        $color = isset($colors[$color]) ? $colors[$color] : $colors['white'];

        return "\033[{$color}m{$text}\033[0m";
    }
}

if (!function_exists('cache_optional')) {
    /**
     * Try to cache $callback response for $minutes
     * in case of exception skip cache
     *
     * @param string $key
     * @param callable $callback
     * @param float $minutes
     * @param string|null $driver
     * @param bool $reset
     * @return mixed
     */
    function cache_optional(
        string $key,
        callable $callback,
        float $minutes = 1,
        string $driver = null,
        bool $reset = false
    ) {
        try {
            $reset && cache()->driver()->delete($key);
            return cache()->driver($driver)->remember($key, $minutes, $callback);
        } catch (\Exception $exception) {
            return $callback();
        }
    }
}

if (!function_exists('record_types_cached')) {
    /**
     * @param float $minutes
     * @param bool $reset
     * @return mixed
     */
    function record_types_cached(
        float $minutes = 1,
        bool $reset = false
    ) {
        return cache_optional('record_types', function() {
            return (array) resolve('forus.services.record')->getRecordTypes();
        }, $minutes, null, $reset);
    }
}

if (!function_exists('pretty_file_size')) {
    /**
     * Human readable file size
     * @param $bytes
     * @param int $precision
     * @return string
     */
    function pretty_file_size(
        $bytes,
        $precision = 2
    ) {
        for ($i = 0; ($bytes / 1024) > 0.9; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) .
            ['','k','M','G','T','P','E','Z','Y'][$i] . 'B';
    }
}


if (!function_exists('service_identity')) {
    /**
     * Get identity service instance
     * @return \App\Services\Forus\Record\Repositories\RecordRepo|mixed
     */
    function service_identity() {
        return resolve('forus.services.identity');
    }
}

if (!function_exists('service_record')) {
    /**
     * Get record service instance
     * @return \App\Services\Forus\Record\Repositories\RecordRepo|mixed
     */
    function service_record() {
        return resolve('forus.services.record');
    }
}

if (!function_exists('json_encode_pretty')) {
    /**
     * @param $value
     * @param int $options
     * @param int $depth
     * @return false|string
     */
    function json_encode_pretty($value, $options = 0, $depth = 512) {
        return json_encode($value, $options + JSON_PRETTY_PRINT, $depth);
    }
}

if (!function_exists('log_debug')) {
    /**
     * @param $message
     * @param array $context
     */
    function log_debug($message, array $context = []) {
        logger()->debug(
            is_string($message) ? $message: json_encode_pretty($message),
            $context
        );
    }
}

if (!function_exists('api_dependency_requested')) {
    /**
     * @param string $key
     * @param \Illuminate\Http\Request|null $request
     * @param bool $default
     * @return bool
     */
    function api_dependency_requested(
        string $key,
        \Illuminate\Http\Request $request = null,
        bool $default = true
    ) {
        $request = $request ?? request();
        $dependency = $request->input('dependency', null);

        if (is_array($dependency)) {
            return in_array($key, $dependency);
        }

        return $default;
    }
}

if (!function_exists('validate_data')) {
    /**
     * @param array $data
     * @param array $rules
     * @return \Illuminate\Validation\Validator
     */
    function validate_data(
        $data = [],
        $rules = []
    ) {
        return \Illuminate\Support\Facades\Validator::make($data, $rules);
    }
}