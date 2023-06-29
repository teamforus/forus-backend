<?php

namespace App\Services\Forus\Auth2FAService\Middleware;

use App\Http\Requests\BaseFormRequest;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class Auth2FAMiddleware
{
    /**
     * @var array
     */
    public const EXCEPT = [
        'proxyDestroy',
        'auth2FAStore',
        'auth2FAState',
        'auth2FAResend',
        'auth2FAActivate',
        'auth2FAAuthenticate',
    ];

    /**
     * @param Request $request
     * @param Closure $next
     * @return JsonResponse|mixed
     */
    public function handle(Request $request, Closure $next): mixed
    {
        if (in_array($request->route()->getName(), static::EXCEPT)) {
            return $next($request);
        }

        $baseRequest = BaseFormRequest::createFrom($request);
        $proxy = $baseRequest->identityProxy();

        if ($proxy && $proxy->identity->is2FARequired() && !$proxy->is2FAConfirmed()) {
            return new JsonResponse([
                'error' => '2fa',
            ], 401);
        }

        return $next($request);
    }
}
