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
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  Closure  $next
     * @param bool $required
     * @param string ...$allowedKeys
     * @return mixed
     */
    public function handle(Request $request, Closure $next, bool $required = false, string ...$allowedKeys): mixed
    {
        if (in_array($request->route()->getName(), $this->except, true)) {
            return $next($request);
        }

        $key = $required ? $request->header('Client-Key') : $this->implementationKey($request);
        $hasRequiredKey = !$required || ($key !== null && $key !== '' && $allowedKeys !== []);
        $isAllowedKey = !$allowedKeys || in_array($key, $allowedKeys, true);

        $isValidKey = $hasRequiredKey && $isAllowedKey &&
            Implementation::implementationKeysAvailable()->search($key) !== false;

        if (!$isValidKey) {
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
