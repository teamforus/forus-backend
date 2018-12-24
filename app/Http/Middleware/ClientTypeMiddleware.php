<?php

namespace App\Http\Middleware;

use App\Models\Implementation;
use Closure;

class ClientTypeMiddleware
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
        if ($this->availableTypes()->search($this->activeType()) === false) {
            return response()->json([
                "message" => 'unknown_client_type'
            ])->setStatusCode(403);
        };

        return $next($request);
    }

    private function availableTypes() {
        return collect([
            'webshop', 'general', 'app-me_app'
        ]);
    }

    private function activeType() {
        return request()->header('Client-Type', 'general');
    }
}
