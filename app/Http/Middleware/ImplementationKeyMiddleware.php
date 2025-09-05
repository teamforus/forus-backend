<?php

namespace App\Http\Middleware;

use App\Http\Requests\BaseFormRequest;
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
        'precheck.events',
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next): mixed
    {
        if (in_array($request->route()->getName(), $this->except, true)) {
            return $next($request);
        }

        if (Implementation::implementationKeysAvailable()->search($this->implementationKey($request)) === false) {
            return new JsonResponse([
                'message' => 'unknown_implementation_key',
            ], 403);
        }

        return $next($request);
    }

    /**
     * @param Request $request
     * @return string|null
     */
    public function implementationKey(Request $request): ?string
    {
        return BaseFormRequest::createFromBase($request)?->implementation_key();
    }
}
