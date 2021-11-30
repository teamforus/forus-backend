<?php

namespace App\Http\Middleware;

use App\Models\Implementation;
use Closure;

class ImplementationKeyMiddleware
{
    /**
     * @var array
     */
    private $except = [
        'status',
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (in_array($request->route()->getName(), $this->except)) {
            return $next($request);
        }

        if (Implementation::implementationKeysAvailable()->search(
            Implementation::activeKey()) === false) {
            return response()->json([
                "message" => 'unknown_implementation_key'
            ])->setStatusCode(403);
        }

        return $next($request);
    }
}
