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
    private $throttle_key_prefix = '';

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
        $this->throttle_key_prefix = ($key ?: $type) . '_';
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
     * @param $key
     */
    protected function clearLoginAttemptsWithKey(
        Request $request,
        string $key
    ): void {
        $this->throttle_key_prefix = $key . '_';
        $this->clearLoginAttempts($request);
        $this->throttle_key_prefix = '';
    }

    /**
     * @param $error
     * @param $request
     * @param string $type
     * @param int $code
     * @throws AuthorizationJsonException
     */
    protected function responseWithThrottleMeta(
        $error,
        $request,
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
            'decay_minutes' => (int) $this->decayMinutes(),
            'max_attempts' => (int) $this->maxAttempts(),
        ];

        $title = trans("throttles/$type.$error.title", $meta);
        $message = trans("throttles/$type.$error.message", $meta);

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
        return Str::lower(($this->throttle_key_prefix ?: '') . $request->ip());
    }
}