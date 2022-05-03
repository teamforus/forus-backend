<?php

namespace App\Services\Forus\Session\Middleware;

use App\Http\Requests\BaseFormRequest;
use Closure;

class ForusSessionMiddleware
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
        $baseRequest = BaseFormRequest::createFromBase($request);

        if ($baseRequest->auth_address() || config('forus.sessions.track_guests')) {
            resolve('forus.session')->makeOrUpdateSession(
                $request->ip(),
                $baseRequest->client_type(config('forus.clients.default')),
                $baseRequest->client_version(),
                $baseRequest->auth_proxy_id(),
                $baseRequest->auth_address()
            );
        }

        return $next($request);
    }
}
