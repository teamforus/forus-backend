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
}
