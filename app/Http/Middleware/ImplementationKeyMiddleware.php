<?php

namespace App\Http\Middleware;

use App\Models\Implementation;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ImplementationKeyMiddleware
{
    /**
     * @var array
     */
    private array $except = [
        'status',
    ];
}
