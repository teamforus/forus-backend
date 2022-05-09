<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;

class ApiAuthMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (!$request->user()) {
            return response()->json([
                "message" => 'invalid_access_token'
            ])->setStatusCode(401);
        }

        // TODO: deprecated, remove after making sure it's not used anywhere
        $proxyId = $request->user()->getProxyId();
        $proxyState = $request->user()->getProxyState();
        $address = $request->user()->getAddress();

        if ($proxyState == 'pending') {
            return new JsonResponse([
                "message" => 'proxy_identity_pending'
            ], 401);
        }

        if (!$proxyId || !$address) {
            return new JsonResponse([
                "message" => 'invalid_access_token'
            ], 401);
        }

        $request->attributes->set('identity', $address);
        $request->attributes->set('proxyIdentity', $proxyId);

        return $next($request);
    }
}
