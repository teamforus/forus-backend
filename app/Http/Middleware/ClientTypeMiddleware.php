<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClientTypeMiddleware
{
    /**
     * @var array
     */
    public const EXCEPT = [
        'status',
        'digidResolve',
        'digidRedirect',
        'emailSignUpRedirect',
        'emailSignInRedirect',
        'bankOauthRedirect',
        'biConnection',
    ];

    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $excludedUrl = in_array($request->route()->getName(), static::EXCEPT, true);
        $validType = in_array($this->activeType($request), $this->availableTypes(), true);

        if (!$excludedUrl && !$validType) {
            return new JsonResponse([
                "message" => 'unknown_client_type',
            ], 403);
        }

        return $next($request);
    }

    /**
     * @return array
     */
    private function availableTypes(): array
    {
        return array_filter(array_flatten(config('forus.clients')));
    }

    /**
     * @param Request $request
     * @return string|null
     */
    private function activeType(Request $request): ?string
    {
        return $request->header('Client-Type', config('forus.clients.default'));
    }
}
