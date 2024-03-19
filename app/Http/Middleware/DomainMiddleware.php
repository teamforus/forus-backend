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
}
