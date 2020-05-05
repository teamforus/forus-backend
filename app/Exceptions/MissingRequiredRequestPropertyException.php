<?php


namespace App\Exceptions;

use Throwable;

class MissingRequiredRequestPropertyException extends \Exception
{
    public function __construct(
        $message = 'Missing required request property.',
        $code = 500,
        Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}