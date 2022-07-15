<?php

namespace App\Http\Middleware;

use App\Http\Requests\BaseFormRequest;
use App\Models\IdentityProxy;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiAuthMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $baseRequest = BaseFormRequest::createFrom($request);

        if ($this->hasExpiredToken($baseRequest)) {
            return new JsonResponse([
                "message" => 'session_expired',
            ], 401);
        }

        if (!$baseRequest->user() || !$baseRequest->identity()) {
            return new JsonResponse([
                "message" => 'invalid_access_token',
            ], 401);
        }

        if ($baseRequest->identityProxy()->isPending()) {
            return new JsonResponse([
                "message" => 'proxy_identity_pending',
            ], 401);
        }

        if (!$baseRequest->identityProxy()->isActive()) {
            return new JsonResponse([
                "message" => 'proxy_identity_not_active',
            ], 401);
        }

        return $next($request);
    }


    /**
     * @param BaseFormRequest $request
     * @return bool
     */
    private function hasExpiredToken(BaseFormRequest $request): bool
    {
        if ($request->isAuthenticated() || !$request->bearerToken()) {
            return false;
        }

        return IdentityProxy::query()
            ->whereAccessToken($request->bearerToken())
            ->where('state', IdentityProxy::STATE_EXPIRED)
            ->withTrashed()
            ->exists();
    }
}
