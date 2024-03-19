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
     * @return array
     */
    private function availableTypes(): array
    {
        return array_filter(array_flatten(config('forus.clients')));
    }

    /**
     * @param Request $request
     *
     * @return array|null|string
     */
    private function activeType(Request $request): array|string|null
    {
        return $request->header('Client-Type', config('forus.clients.default'));
    }
}
