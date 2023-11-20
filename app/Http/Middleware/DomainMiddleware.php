<?php

namespace App\Http\Middleware;

use Closure;

/**
 * Class DomainMiddleware
 * @package App\Http\Middleware
 */
class DomainMiddleware
{
    /**
     * @var array
     */
    private $except = [
        'status',
        'digidStart',
        'digidResolve',
        'digidRedirect',
        'mollie.webhook',
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
        $exclude = in_array($request->route()->getName(), $this->except);

        if (!$exclude && !env('DISABLE_DOMAIN_VERIFICATION')) {
            $appDomain = parse_url(env('APP_URL', ''))['host'];
            $requestDomain = $request->getHost();

            if ($appDomain != $requestDomain) {
                return response()->json([
                    "message" => 'invalid_host_name'
                ])->setStatusCode(403);
            }
        }

        return $next($request);
    }
}
