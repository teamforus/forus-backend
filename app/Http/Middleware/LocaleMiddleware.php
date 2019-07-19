<?php

namespace App\Http\Middleware;

use Closure;

class LocaleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $locales = config('translatable.locales', []);
        $locale = $request->header('Accept-Language', 'nl');

        if (in_array($locale, $locales)) {
            app()->setLocale($locale);
        }

        return $next($request);
    }
}
