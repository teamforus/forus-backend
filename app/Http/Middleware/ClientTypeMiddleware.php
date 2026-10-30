<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class ClientTypeMiddleware
{
    /**
     * @var array
     */
    public const array EXCEPT = [
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
     * @param string ...$allowedTypes
     * @return mixed
     */
    public function handle(Request $request, Closure $next, string ...$allowedTypes): mixed
    {
        $excludedUrl = in_array($request->route()->getName(), static::EXCEPT, true);
        $type = $allowedTypes ? $request->header('Client-Type') : $this->activeType($request);

        $isKnownType = in_array($type, $this->availableTypes(), true);
        $isAllowedType = !$allowedTypes || in_array($type, $allowedTypes, true);

        if (!$excludedUrl && !($isKnownType && $isAllowedType)) {
            return new JsonResponse([
                'message' => 'unknown_client_type',
            ], 403);
        }

        return $next($request);
    }

    /**
     * @return array
     */
    private function availableTypes(): array
    {
        return array_filter(Arr::flatten(config('forus.clients')));
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
