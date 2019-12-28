<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\TransformsRequest;

class ParseApiDependencyMiddleware extends TransformsRequest
{
    protected function transform($key, $value)
    {
        if (($key === 'dependency') && is_string($value)) {
            return empty($value) ? [] : explode(',', $value);
        }

        return $value;
    }
}
