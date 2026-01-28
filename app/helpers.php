<?php

use App\Models\RecordType;
use App\Services\Forus\Session\Services\Browser;
use App\Services\Forus\Session\Services\Data\AgentData;
use App\Services\TokenGeneratorService\TokenGenerator;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder as QBuilder;
use Illuminate\Support\Str;

if (!function_exists('format_datetime_locale')) {
    /**
     * @param $date
     * @param string $format
     * @return string|null
     */
    function format_datetime_locale($date, string $format = 'short_date_time_locale'): ?string
    {
        try {
            if (is_string($date)) {
                $date = new Carbon($date);
            }

            return str_replace('.', '', $date->isoFormat(config("forus.formats.$format") ?: $format));
        } catch (Throwable) {
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
    function format_date_locale($date = null, string $format = 'short_date_locale'): ?string
    {
        if (is_null($date)) {
            return null;
        }

        try {
            if (is_string($date)) {
                $date = new Carbon($date);
            }

            return str_replace('.', '', $date->isoFormat(config("forus.formats.$format") ?: $format));
        } catch (Throwable) {
            return is_string($date) ? $date : null;
        }
    }
}

if (!function_exists('currency_format')) {
    /**
     * @param $number
     * @param int $decimals
     * @param string $decPoint
     * @param string $thousandsSep
     * @return string
     */
    function currency_format(
        $number,
        int $decimals = 2,
        string $decPoint = '.',
        string $thousandsSep = ''
    ): string {
        return number_format(is_null($number) ? 0 : $number, $decimals, $decPoint, $thousandsSep);
    }
}

if (!function_exists('currency_format_locale')) {
    /**
     * @param $number
     * @return string
     */
    function currency_format_locale($number): string
    {
        $number = is_null($number) ? 0 : $number;
        $isWhole = ($number - round($number)) === 0.0;

        return '€ ' . implode('', [
            currency_format($number, $isWhole ? 0 : 2, ',', '.'),
            $isWhole ? ',-' : '',
        ]);
    }
}

if (!function_exists('cache_optional')) {
    /**
     * Try to cache $callback response for $minutes in case of exception skip cache.
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
    ): mixed {
        try {
            $reset && cache()->driver()->delete($key);

            return cache()->driver($driver)->remember($key, $minutes * 60, $callback);
        } catch (\Psr\SimpleCache\CacheException|\Throwable) {
            return $callback();
        }
    }
}

if (!function_exists('record_types_cached')) {
    /**
     * @param float $minutes
     * @param bool $reset
     * @return array[]
     */
    function record_types_cached(
        float $minutes = 1,
        bool $reset = false
    ): mixed {
        return cache_optional('record_types', static function () {
            return RecordType::search()->toArray();
        }, $minutes, null, $reset);
    }
}

if (!function_exists('record_types_static')) {
    /**
     * @return RecordType[]|Builder[]|Collection|null
     */
    function record_types_static(): Collection|array|null
    {
        static $cache = null;

        if ($cache === null) {
            $cache = RecordType::searchQuery()->with('translations')->get();
        }

        return $cache->keyBy('key');
    }
}

if (!function_exists('pretty_file_size')) {
    /**
     * Human-readable file size.
     * @param $bytes
     * @param int $precision
     * @return string
     */
    function pretty_file_size($bytes, int $precision = 2): string
    {
        for ($i = 0; ($bytes / 1024) > 0.9; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) .
            ['', 'k', 'M', 'G', 'T', 'P', 'E', 'Z', 'Y'][$i] . 'B';
    }
}

if (!function_exists('json_pretty')) {
    /**
     * @param mixed $value
     * @param int $options
     * @param int $depth
     * @return false|string
     */
    function json_pretty(mixed $value, int $options = 0, int $depth = 512): string|false
    {
        try {
            return json_encode($value, JSON_THROW_ON_ERROR | $options + JSON_PRETTY_PRINT, $depth);
        } catch (Throwable) {
        }

        return $value;
    }
}

if (!function_exists('log_debug')) {
    /**
     * @param mixed ...$arg
     * @return string|false|null
     */
    function log_debug(...$arg): string|null|false
    {
        if (!is_null($logger = logger())) {
            $logger->debug(json_pretty($arg));

            return json_pretty($arg);
        }

        return null;
    }
}

if (!function_exists('filter_bool')) {
    /**
     * @param $value
     * @return bool
     */
    function filter_bool($value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
}

if (!function_exists('url_extend_get_params')) {
    /**
     * @param string $url
     * @param array $params
     * @return string
     */
    function url_extend_get_params(string $url, array $params = []): string
    {
        $urlData = explode('?', rtrim($url, '/'));
        $urlParams = [];
        parse_str($urlData[1] ?? '', $urlParams);

        return sprintf('%s?%s', rtrim($urlData[0], '/'), http_build_query(array_merge(
            $params,
            $urlParams
        )));
    }
}

if (!function_exists('http_resolve_url')) {
    /**
     * @param string $url
     * @param string $uri
     * @return string
     */
    function http_resolve_url(string $url, string $uri = ''): string
    {
        return url(sprintf('%s/%s', rtrim($url, '/'), ltrim($uri, '/')));
    }
}

if (!function_exists('make_qr_code')) {
    /**
     * @param string $type
     * @param string $value
     * @param int $size
     * @return string
     */
    function make_qr_code(string $type, string $value, int $size = 400): string
    {
        return (string) QrCode::format('png')
            ->size($size)
            ->margin(2)
            ->generate(json_encode(compact('type', 'value')));
    }
}

if (!function_exists('token_generator')) {
    /**
     * @return TokenGenerator
     */
    function token_generator(): TokenGenerator
    {
        return resolve('token_generator');
    }
}

if (!function_exists('trans_fb')) {
    /**
     * Translate the given message with a fallback string if none exists.
     *
     * @param string $id
     * @param string|array $fallback
     * @param array|null $parameters
     * @param string|null $locale
     * @return string|array
     */
    function trans_fb(
        string $id,
        string|array $fallback,
        ?array $parameters = [],
        ?string $locale = null,
    ): string|array {
        return ($id === ($translation = trans($id, $parameters, $locale))) ? $fallback : $translation;
    }
}

if (!function_exists('str_var_replace')) {
    function str_var_replace(string $string, array $replace, bool $filterValues = true): string
    {
        $replace = array_sort($replace, fn ($value, $key) => mb_strlen($key) * -1);

        foreach ($replace as $key => $value) {
            if (!$filterValues || (is_string($value) || is_numeric($value))) {
                $string = str_replace(
                    [':' . $key, ':' . Str::upper($key), ':' . Str::ucfirst($key)],
                    [$value, Str::upper($value), Str::ucfirst($value)],
                    $string,
                );
            }
        }

        return $string;
    }
}

if (!function_exists('user_agent_data')) {
    /**
     * @param null $user_agent
     * @return AgentData
     */
    function user_agent_data($user_agent = null): AgentData
    {
        return Browser::getAgentData($user_agent ?: request()->userAgent() ?: '');
    }
}

if (!function_exists('query_to_sql')) {
    /**
     * Converts a query builder instance to its SQL representation with bindings.
     *
     * @param Builder|QBuilder|Relation $builder The query builder instance to convert.
     * @return string The SQL representation of the query with bound values.
     */
    function query_to_sql(Builder|QBuilder|Relation $builder): string
    {
        $bindings = array_map(function ($binding) {
            return '"' . htmlspecialchars($binding) . '"';
        }, $builder->getBindings());

        return str_replace_array('?', $bindings, $builder->toSql());
    }
}

if (!function_exists('array_replace_values')) {
    /**
     * Replaces values in an array based on a replacements map.
     *
     * @param array $items The original array of items to be processed.
     * @param array $replacements A key-value associative array where keys are the items to replace and values are their replacements.
     * @return array An array with replaced values according to the replacements map.
     */
    function array_replace_values(array $items, array $replacements): array
    {
        return array_map(
            fn (string $item): string => array_key_exists($item, $replacements)
                ? $replacements[$item]
                : $item,
            $items
        );
    }
}
