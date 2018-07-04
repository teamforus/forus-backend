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
        $bearerToken = explode(' ', $request->headers->get('Authorization'));
        $accessToken = count($bearerToken) == 2 ? $bearerToken[1] : null;

        $identityService = app()->make('forus.services.identity');

        $proxyIdentityId = $identityService->proxyIdByAccessToken($accessToken);
        $proxyIdentityState = $identityService->proxyStateById($proxyIdentityId);
        $identityAddress = $identityService->identityAddressByProxyId($proxyIdentityId);

        if ($accessToken && $proxyIdentityState != 'active') {
            switch ($proxyIdentityState) {
                case 'pending': {
                    return response()->json([
                        "message" => 'proxy_identity_pending'
                    ])->setStatusCode(401);
                } break;
            }
        }

        if (!$accessToken || !$proxyIdentityId || !$identityAddress) {
            return response()->json([
                "message" => 'invalid_access_token'
            ])->setStatusCode(401);
        }

        $request->attributes->set('identity', $identityAddress);
        $request->attributes->set('proxyIdentity', $proxyIdentityId);

        return $next($request);
    }
}
