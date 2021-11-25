<?php

namespace App\Traits;

use App\Exceptions\AuthorizationJsonException;
use Illuminate\Foundation\Auth\ThrottlesLogins;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Trait ThrottleWithMeta
 * @property int $decayMinutes
 * @property int $maxAttempts
 */
trait ThrottleWithMeta {
    use ThrottlesLogins;

    /**
     * @var string
     */
    private $throttleKeyPrefix = '';

    /**
     * @param string $error
     * @param Request $request
     * @param string $type
     * @param ?string $key
     * @param int $code
     * @throws AuthorizationJsonException
     */
    protected function throttleWithKey(
        string $error,
        Request $request,
        string $type,
        ?string $key = null,
        $code = 429
    ): void {
        $this->throttleKeyPrefix = ($key ?: $type) . '_';
        $this->throttle($error, $request, $type, $code);
    }

    /**
     * @param string $error
     * @param Request $request
     * @param string $type
     * @param int $code
     * @throws AuthorizationJsonException
     */
    private function throttle(
        string $error,
        Request $request,
        string $type = 'prevalidations',
        $code = 429
    ): void {
        if ($this->hasTooManyLoginAttempts($request)) {
            $this->responseWithThrottleMeta($error, $request, $type, $code);
        }

        $this->incrementLoginAttempts($request);
    }

    /**
     * @param Request $request
     * @param string $key
     * @noinspection PhpUnused
     */
    protected function clearLoginAttemptsWithKey(Request $request, string $key): void
    {
        $this->throttleKeyPrefix = $key . '_';
        $this->clearLoginAttempts($request);
        $this->throttleKeyPrefix = '';
    }

    /**
     * @param $error
     * @param Request $request
     * @param string $type
     * @param int $code
     * @throws AuthorizationJsonException
     */
    protected function responseWithThrottleMeta(
        $error,
        Request $request,
        string $type = 'prevalidations',
        $code = 429
    ): void {
        $key = $this->throttleKey($request);
        $available_in = $this->limiter()->tooManyAttempts($key, $this->maxAttempts()) ?
            $this->limiter()->availableIn($key) : null;
        $available_in_min = $available_in !== null ? ceil($available_in / 60) : null;

        $meta = [
            'error' => 'not_found',
            'attempts' => $this->limiter()->attempts($key),
            'available_in' => $available_in,
            'available_in_min' => $available_in_min,
            'decay_minutes' => $this->decayMinutes(),
            'max_attempts' => $this->maxAttempts(),
        ];

        $title = trans("throttles/$type.$error.title", $meta);
        $message = trans("throttles/$type.$error.message", $meta);

        if (!$request->expectsJson()) {
            abort($code, $message);
        }

        throw new AuthorizationJsonException(json_encode([
            'error' => $error,
            'message' => $message,
            'meta' => array_merge([
                'title' => $title,
                'message' => $message,
            ], $meta)
        ]), $code);
    }

    /**
     * Get the throttle key for the given request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string
     */
    protected function throttleKey(Request $request): string
    {
        return Str::lower(($this->throttleKeyPrefix ?: '') . $request->ip());
    }
}