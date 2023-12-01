<?php

namespace App\Http\Middleware;

use App\Models\Implementation;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ImplementationKeyMiddleware
{
    /**
     * @var array
     */
    private array $except = [
        'status',
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next): mixed
    {
        if (in_array($request->route()->getName(), $this->except, true)) {
            return $next($request);
        }

        if (Implementation::implementationKeysAvailable()->search(Implementation::activeKey()) === false) {
            return new JsonResponse([
                "message" => 'unknown_implementation_key',
            ], 403);
        }

        return $next($request);
    }
}
