<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

abstract class BaseRule implements Rule
{
    protected string $messageText = '';

    /**
     * @param $message
     * @return bool
     */
    public function reject($message): bool
    {
        $this->messageText = $message;

        return false;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message(): string
    {
        return $this->messageText;
    }
}
