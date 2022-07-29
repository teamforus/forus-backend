<?php

namespace App\Http\Middleware;

use App\Http\Requests\BaseFormRequest;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClientVersionMiddleware
{
    /**
     * @var array
     */
    public const EXCEPT = ClientTypeMiddleware::EXCEPT;

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $excluded = in_array($request->route()->getName(), static::EXCEPT);
        $baseRequest = BaseFormRequest::createFrom($request);
        $clientVersion = $baseRequest->client_version();

        if ($excluded || is_null($clientVersion) || is_numeric($clientVersion)) {
            return $next($request);
        }

        return new JsonResponse([
            "message" => 'invalid_client_version',
        ], 403);
    }
}
