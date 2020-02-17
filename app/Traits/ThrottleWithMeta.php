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
     * @param $error
     * @param $request
     * @param int $code
     * @throws AuthorizationJsonException
     */
    protected function responseWithThrottleMeta(
        $error,
        $request,
        $code = 429
    ) {
        $key = $this->throttleKey($request);
        $available_in = $this->limiter()->tooManyAttempts(
            $key, $this->maxAttempts()
        ) ? $this->limiter()->availableIn($key) : null;
        $available_in_min = $available_in != null ? ceil($available_in / 60) : null;

        $meta = [
            'error' => 'not_found',
            'attempts' => $this->limiter()->attempts($key),
            'available_in' => $available_in,
            'available_in_min' => $available_in_min,
            'decay_minutes' => $this->decayMinutes(),
            'max_attempts' => $this->maxAttempts(),
        ];

        $title = trans("throttles/prevalidations.$error.title", $meta);
        $message = trans("throttles/prevalidations.$error.message", $meta);

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
    protected function throttleKey(Request $request)
    {
        return Str::lower($request->ip());
    }
}