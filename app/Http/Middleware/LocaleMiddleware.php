<?php

namespace App\Http\Middleware;

use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Lang;
use App\Models\Language;

class LocaleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $locales = Language::getSupportedLocales([config('app.locale')]);
        $locale = $request->header('Accept-Language', 'nl');

        if (in_array($locale, $locales)) {
            Lang::setLocale($locale);
            Carbon::setLocale($locale);
        }

        return $next($request);
    }
}
