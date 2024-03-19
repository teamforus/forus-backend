<?php

namespace App\Http\Middleware;

use App\Http\Requests\BaseFormRequest;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClientVersionMiddleware
{
    /**
     * @var array
     */
    public const EXCEPT = ClientTypeMiddleware::EXCEPT;
}
