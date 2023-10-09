<?php


namespace App\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;

class AuthorizationJsonException extends \Exception
{
    /**
     * @return string
     */
    public function __toString(): string
    {
        return gettype($this->message);
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function render(): JsonResponse
    {
        return new JsonResponse(array_merge(
            json_decode($this->message, JSON_OBJECT_AS_ARRAY), config('app.debug', false) ? [
                'file' => $this->getFile(),
                'line' => $this->getLine(),
                'trace' => array_map(fn ($trace) => Arr::except($trace, ['args']), $this->getTrace()),
            ]: []
        ), $this->getCode());
    }

}