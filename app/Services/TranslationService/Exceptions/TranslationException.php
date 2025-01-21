<?php

namespace App\Services\TranslationService\Exceptions;

use Exception;

class TranslationException extends Exception
{
    protected function hasResponse(): bool
    {
        return false;
    }
}