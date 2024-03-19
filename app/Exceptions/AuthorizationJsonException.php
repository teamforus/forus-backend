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

}