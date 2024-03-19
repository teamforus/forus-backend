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
