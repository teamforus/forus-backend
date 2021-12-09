<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Class ClientTypeMiddleware
 * @package App\Http\Middleware
 */
class ClientTypeMiddleware
{
    /**
     * @var array
     */
    private $except = [
        'status',
        'digidResolve',
        'digidRedirect',
        'emailSignUpRedirect',
        'emailSignInRedirect',
        'bankOauthRedirect',
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $exclude = in_array($request->route()->getName(), $this->except);

        if (!$exclude && !in_array(
            $this->activeType($request),
            $this->availableTypes())) {
            return response()->json([
                "message" => 'unknown_client_type'
            ])->setStatusCode(403);
        }

        return $next($request);
    }

    /**
     * @return array
     */
    private function availableTypes(): array {
        return array_filter(array_flatten(config('forus.clients')));
    }

    /**
     * @param Request $request
     * @return array|string|null
     */
    private function activeType(Request $request) {
        return $request->header('Client-Type', config('forus.clients.default'));
    }
}
