<?php

namespace App\Http\Middleware;

use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Lang;
use App\Models\Language;

class LocaleMiddleware
{
    /**
     * Routes to be excluded from locale setting.
     *
     * @var array
     */
    protected array $excludedRoutes = [
        'pre-check.download-pdf',
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
        if ($this->isExcluded($request)) {
            return $next($request);
        }

        $defaultLocale = Config::get('app.locale');
        $locales = Language::getSupportedLocales([$defaultLocale]);
        $locale = $request->header('Accept-Language', $defaultLocale);

        if (in_array($locale, $locales)) {
            Lang::setLocale($locale);
            Carbon::setLocale($locale);
        }

        return $next($request);
    }

    /**
     * Check if the current route is excluded from locale setting.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    protected function isExcluded(Request $request): bool
    {
        $route = $request->route();
        return $route && in_array($route->getName(), $this->excludedRoutes, true);
    }
}
