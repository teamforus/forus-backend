<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ClientTypeMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if (!in_array($this->activeType($request), $this->availableTypes())) {
            return response()->json([
                "message" => 'unknown_client_type'
            ])->setStatusCode(403);
        }

        return $next($request);
    }

    private function availableTypes() {
        return array_flatten(config('forus.clients'));
    }

    private function activeType(Request $request) {
        return $request->header('Client-Type', config('forus.clients.default'));
    }
}
