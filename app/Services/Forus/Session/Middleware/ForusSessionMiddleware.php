<?php

namespace App\Services\Forus\Session\Middleware;

use App\Http\Requests\BaseFormRequest;
use App\Models\IdentityProxy;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ForusSessionMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return mixed
     * @throws \Exception
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $baseRequest = BaseFormRequest::createFrom($request);

        if ($baseRequest->isAuthenticated() || config('forus.sessions.track_guests')) {
            resolve('forus.session')->makeOrUpdateSession(
                $request->ip(),
                $baseRequest->client_type(config('forus.clients.default')),
                $baseRequest->client_version(),
                $baseRequest->identityProxy()?->id,
                $baseRequest->auth_address()
            );
        }

        return $next($request);
    }
}
