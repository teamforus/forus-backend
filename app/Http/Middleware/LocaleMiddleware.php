<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Lang;

class LocaleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $locales = config('translatable.locales', []);
        $locale = $request->header('Accept-Language', 'nl');

        if (in_array($locale, $locales)) {
            Lang::setLocale($locale);
        }

        return $next($request);
    }
}
