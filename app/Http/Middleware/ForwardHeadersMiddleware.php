<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;

class ForwardHeadersMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $forward = array_filter([
            'X-Client-Key'    => $request->header('client-key'),
            'X-Client-Type'   => $request->header('client-type'),
            'X-Locale'        => app()->getLocale(),

            // Observability and correlation
            'X-Request-Id'    => $request->header('X-Request-Id'),
            'traceparent'     => $request->header('traceparent'),
            'tracestate'      => $request->header('tracestate'),

            // Traceability
            'X-Forwarded-For' => $request->ip(),
        ], fn ($value) => !is_null($value) && $value !== '');

        $request->attributes->set('forward_headers', $forward);

        return $next($request);
    }
}