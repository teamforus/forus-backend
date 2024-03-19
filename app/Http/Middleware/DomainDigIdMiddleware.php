<?php

namespace App\Http\Middleware;

use App\Models\Implementation;
use Closure;
use Illuminate\Http\Request;

/**
 * Class DomainDigIdMiddleware
 * @package App\Http\Middleware
 */
class DomainDigIdMiddleware
{
    /**
     * @var bool
     */
    protected $strictDomain = false;

    /**
     * @param Request $request
     * @return bool
     */
    protected function isHostAllowed(Request $request): bool
    {
        $appUrl = env('APP_URL', '');
        $allowedDomains = Implementation::pluck('digid_forus_api_url')->push($appUrl)->filter();

        // use domain names in the future
        if ($this->strictDomain) {
            return $allowedDomains->map(function($url) {
                return $url && is_string($url) ? parse_url($url)['host'] : false;
            })->values()->search($request->getHost()) !== false;
        }

        return $allowedDomains->filter(function($url) use ($request) {
            return starts_with($request->url(), $url);
        })->isNotEmpty();
    }
}
