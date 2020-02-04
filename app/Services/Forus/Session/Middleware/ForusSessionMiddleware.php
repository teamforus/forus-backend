<?php

namespace App\Services\Forus\Session\Middleware;

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
        if (auth_address() || config('forus.sessions.track_guests')) {
            resolve('forus.session')->makeOrUpdateSession(
                $request->ip(),
                client_type(config('forus.clients.default')),
                client_version(),
                auth_proxy_id(),
                auth_address()
            );
        }

        return $next($request);
    }
}
