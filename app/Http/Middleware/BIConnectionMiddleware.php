<?php

namespace App\Http\Middleware;

use App\Models\BIConnection;
use Closure;

class BIConnectionMiddleware
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
        if (!BIConnection::getConnectionFromRequest($request)) {
            return response()->json([
                'message' => 'invalid_api_key'
            ])->setStatusCode(403);
        }

        return $next($request);
    }
}
