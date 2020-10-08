<?php


namespace App\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;

class AuthorizationJsonException extends \Exception
{
    /**
     * @return string
     */
    public function __toString()
    {
        return gettype($this->message);
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function render(): JsonResponse
    {
        return response()->json(array_merge(
            json_decode($this->message, JSON_OBJECT_AS_ARRAY), config('app.debug', false) ? [
                'file' => $this->getFile(),
                'line' => $this->getLine(),
                'trace' => collect($this->getTrace())->map(function ($trace) {
                    return Arr::except($trace, ['args']);
                })->all(),
            ]: []
        ), $this->getCode());
    }

}