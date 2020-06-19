<?php

use App\Models\Implementation;
use \Carbon\Carbon;
use \Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Builder;
use \App\Services\Forus\Session\Services\Browser;

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
        return auth()->user() ? auth()->user()->getAddress() : null;
    }
}

if (!function_exists('auth_proxy_id')) {
    /**
     * Get the available user instance.
     *
     * @return string|null
     */
    function auth_proxy_id()
    {
        return auth()->user() ? auth()->user()->getProxyId() : null;
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
     * @param $date
     * @param string $format
     * @return string|null
     */
    function format_date($date, string $format = 'short_date_time') {
        try {
            if (is_string($date)) {
                $date = new Carbon($date);
            }

            return $date->formatLocalized(
                config("forus.formats.$format") ?: $format
            );
        } catch (Throwable $throwable) {
            return is_string($date) ? $date : null;
        }
    }
}

if (!function_exists('format_datetime_locale')) {
    /**
     * @param $date
     * @param string $format
     * @return string|null
     */
    function format_datetime_locale($date, string $format = 'short_date_time_locale') {
        try {
            if (is_string($date)) {
                $date = new Carbon($date);
            }

            return $date->formatLocalized(
                config("forus.formats.$format") ?: $format
            );
        } catch (Throwable $throwable) {
            return is_string($date) ? $date : null;
        }
    }
}

if (!function_exists('format_date_locale')) {
    /**
     * @param null $date
     * @param string $format
     * @return string|null
     */
    function format_date_locale(
        $date = null,
        string $format = 'short_date_locale'
    ) {
        try {
            if (is_string($date)) {
                $date = new Carbon($date);
            }

            return $date->formatLocalized(
                config("forus.formats.$format") ?: $format
            );
        } catch (Throwable $throwable) {
            return is_string($date) ? $date : null;
        }
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
        return ($number % 1 === 0 ? (int) $number : currency_format($number)) . ',-';
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
            (float) (is_numeric($number) ? $number : 0),
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
        $normalizeGuessedAbilityName = static function ($ability) {
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

        $parseAbilityAndArguments = static function ($ability, $arguments)  use ($normalizeGuessedAbilityName) {
            if (is_string($ability) && strpos($ability, '\\') === false) {
                return [$ability, $arguments];
            }

            $method = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3)[2]['function'];

            return [$normalizeGuessedAbilityName($method), $ability];
        };

        [$ability, $arguments] = $parseAbilityAndArguments($ability, $arguments);

        return app(Gate::class)->authorize($ability, $arguments);
    }
}

if (!function_exists('implementation_key')) {
    /**
     * @param string $default
     * @return array|string|null
     */
    function implementation_key($default = 'general') {
        return request()->header('Client-Key', $default);
    }
}

if (!function_exists('client_type')) {
    /**
     * @param null $default
     * @return array|string
     */
    function client_type($default = null) {
        return request()->header('Client-Type', $default);
    }
}

if (!function_exists('client_version')) {
    /**
     * @param null $default
     * @return array|string
     */
    function client_version($default = null) {
        return request()->header('Client-Version', $default);
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
        $implementationKey = $implementation ?: Implementation::activeKey();
        $configKey = "forus.mails.implementations.%s.$key";

        if (config()->has(sprintf($configKey, $implementationKey))) {
            return config()->get(sprintf($configKey, $implementationKey));
        }

        if (config()->has(sprintf($configKey, 'general'))) {
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

        $color = $colors[$color] ?? $colors['white'];

        return "\033[{$color}m{$text}\033[0m";
    }
}

if (!function_exists('cache_optional')) {
    /**
     * Try to cache $callback response for $minutes in case of exception skip cache
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
            return cache()->driver($driver)->remember($key, $minutes * 60, $callback);
        } catch (\Psr\SimpleCache\CacheException $throwable) {
            return $callback();
        } catch (\Throwable $throwable) {
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
        return cache_optional('record_types', static function() {
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

if (!function_exists('json_pretty')) {
    /**
     * @param $value
     * @param int $options
     * @param int $depth
     * @return false|string
     */
    function json_pretty($value, $options = 0, $depth = 512) {
        return json_encode($value, $options + JSON_PRETTY_PRINT, $depth);
    }
}

if (!function_exists('log_debug')) {
    /**
     * @param $message
     * @param array $context
     */
    function log_debug($message, array $context = []) {
        if (!is_null($logger = logger())) {
            $logger->debug(
                is_string($message) ? $message : json_pretty($message),
                $context
            );
        }
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
        $requestData = $request ?? request();
        $dependency = $requestData->input('dependency', null);

        if (is_array($dependency)) {
            return in_array($key, $dependency, true);
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

if (!function_exists('filter_bool')) {
    /**
     * @param $value
     * @return bool
     */
    function filter_bool($value) {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
}

if (!function_exists('url_extend_get_params')) {
    /**
     * @param string $url
     * @param array $params
     * @return string
     */
    function url_extend_get_params(string $url, array $params = []) {
        $urlData = explode('?', rtrim($url, '/'));

        $urlParams = [];
        parse_str($urlData[1] ?? "", $urlParams);

        return sprintf("%s?%s", rtrim($urlData[0], '/'), http_build_query(array_merge(
            $params, $urlParams
        )));
    }
}

if (!function_exists('http_resolve_url')) {
    /**
     * @param string $url
     * @param string $uri
     * @return string
     */
    function http_resolve_url(string $url, string $uri = ''): string {
        return url(sprintf('%s/%s', rtrim($url, '/'), ltrim($uri, '/')));
    }
}

if (!function_exists('range_between_dates')) {
    /**
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @param null $countDates
     * @return \Illuminate\Support\Collection|Carbon[]
     */
    function range_between_dates(
        Carbon $startDate,
        Carbon $endDate,
        $countDates = null
    ) {
        $dates = collect();
        $diffBetweenDates = $startDate->diffInDays($endDate);

        if ($startDate->isSameDay($endDate)) {
            return $dates->push($endDate);
        }

        if (!$countDates) {
            for ($i = 0; $i <= $diffBetweenDates; $i++) {
                $dates->push($startDate->copy()->addDays($i));
            }

            return $dates;
        }

        $countDates--;
        $countDates = min($countDates, $diffBetweenDates);
        $interval = $diffBetweenDates / $countDates;

        if ($diffBetweenDates > 1) {
            for ($i = 0; $i < $countDates; $i++) {
                $dates->push($startDate->copy()->addDays($i * $interval));
            }
        }

        $dates->push($endDate);

        return $dates;
    }
}

if (!function_exists('make_qr_code')) {
    /**
     * @param string $type
     * @param string $value
     * @return string|void
     */
    function make_qr_code(string $type, string $value) {
        return QrCode::format('png')->size(400)->margin(2)->generate(
            json_encode(compact('type', 'value'))
        );
    }
}

if (!function_exists('token_generator')) {
    /**
     * @return \App\Services\TokenGeneratorService\TokenGenerator|mixed
     */
    function token_generator() {
        return resolve('token_generator');
    }
}


if (!function_exists('trans_fb')) {
    /**
     * Translate the given message with a fallback string if none exists.
     *
     * @param string $id
     * @param string $fallback
     * @param array $parameters
     * @param string $locale
     * @return \Symfony\Component\Translation\TranslatorInterface|string
     */
    function trans_fb($id, $fallback, $parameters = [], $locale = null)
    {
        return ($id === ($translation = trans($id, $parameters, $locale))) ? $fallback : $translation;
    }
}

if (!function_exists('str_var_replace')) {
    function str_var_replace($string, $replace)
    {
        foreach ($replace as $key => $value) {
            $string = str_replace(
                [':'.$key, ':' . Str::upper($key), ':' . Str::ucfirst($key)],
                [$value, Str::upper($value), Str::ucfirst($value)],
                $string
            );
        }

        return $string;
    }
}

if (!function_exists('record_repo')) {
    /**
     * @return \App\Services\Forus\Record\Repositories\Interfaces\IRecordRepo
     */
    function record_repo()
    {
        return resolve('forus.services.record');
    }
}

if (!function_exists('identity_repo')) {
    /**
     * @return \App\Services\Forus\Identity\Repositories\Interfaces\IIdentityRepo
     */
    function identity_repo()
    {
        return resolve('forus.services.identity');
    }
}

if (!function_exists('query_with_trashed')) {
    /**
     * @param Builder|\Illuminate\Database\Eloquent\SoftDeletes|\Illuminate\Database\Eloquent\Relations\Relation $builder
     * @return mixed
     */
    function query_with_trashed($builder)
    {
        return $builder->withTrashed();
    }
}


if (!function_exists('user_agent_data')) {
    /**
     * @param null $user_agent
     * @return \App\Services\Forus\Session\Services\Data\AgentData|null
     */
    function user_agent_data($user_agent = null)
    {
        return Browser::getAgentData($user_agent ?: request()->userAgent());
    }
}
