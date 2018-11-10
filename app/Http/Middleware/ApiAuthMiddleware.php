<?php

namespace App\Http\Middleware;

use Closure;

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
        $proxyId = $request->user()->getProxyId();
        $proxyState = $request->user()->getProxyState();
        $address = $request->user()->getAddress();

        switch ($proxyState) {
            case 'pending': {
                return response()->json([
                    "message" => 'proxy_identity_pending'
                ])->setStatusCode(401);
            } break;
        }

        if (!$proxyId || !$address) {
            return response()->json([
                "message" => 'invalid_access_token'
            ])->setStatusCode(401);
        }

        $request->attributes->set('identity', $address);
        $request->attributes->set('proxyIdentity', $proxyId);

        return $next($request);
    }
}
