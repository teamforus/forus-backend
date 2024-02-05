<?php

namespace App\Services\MollieService\Exceptions;

use Exception;

class MollieException extends Exception {
    protected function hasResponse(): bool
    {
        return false;
    }
}