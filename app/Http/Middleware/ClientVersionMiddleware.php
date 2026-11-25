<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClientVersionMiddleware
{
    /**
     * @var array
     */
    public const array EXCEPT = ClientTypeMiddleware::EXCEPT;

    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @param bool $required
     * @return mixed
     */
    public function handle(Request $request, Closure $next, bool $required = false): mixed
    {
        $excluded = in_array($request->route()->getName(), static::EXCEPT);
        $clientVersion = $request->header('Client-Version');

        $isValidVersion = $required
            ? filter_var($clientVersion, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) !== false
            : is_null($clientVersion) || is_numeric($clientVersion);

        if ($excluded || $isValidVersion) {
            return $next($request);
        }

        return new JsonResponse([
            'message' => 'invalid_client_version',
        ], 403);
    }
}
