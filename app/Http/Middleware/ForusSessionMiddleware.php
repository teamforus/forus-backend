<?php

namespace App\Http\Middleware;

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
        resolve('forus.session')->updateSession(
            $request->ip(),
            client_type(config('forus.clients.default')),
            client_version(),
            $request->user() ? $request->user()->getProxyId() : null,
            $request->user() ? $request->user()->getAddress() : null
        );

        return $next($request);
    }
}
