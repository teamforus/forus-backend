<?php


namespace App\Exceptions;


use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class AuthorizationJsonException extends \Exception
{
    public function __toString()
    {
        return gettype($this->message);
    }

    public function render(Request $request)
    {
        return response()->json(array_merge(
            json_decode($this->message, JSON_OBJECT_AS_ARRAY), config('app.debug') ? [
                'file' => $this->getFile(),
                'line' => $this->getLine(),
                'trace' => collect($this->getTrace())->map(function ($trace) {
                    return Arr::except($trace, ['args']);
                })->all(),
            ]: []
        ), $this->getCode());
    }

}