<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;

class DomainMiddleware
{
    /**
     * @var array
     */
    private array $except = [
        'status',
        'digidStart',
        'digidResolve',
        'digidRedirect',
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
        $exclude = in_array($request->route()->getName(), $this->except, true);

        if (!$exclude && !Config::get('forus.domain.disable_domain_verification')) {
            $appDomain = parse_url(Config::get('app.url', ''))['host'];
            $requestDomain = $request->getHost();

            if ($appDomain !== $requestDomain) {
                return new JsonResponse([
                    "message" => 'invalid_host_name',
                ], 403);
            }
        }

        return $next($request);
    }
}
