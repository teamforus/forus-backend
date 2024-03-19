<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

abstract class BaseRule implements Rule
{
    protected string $messageTransPrefix = "";
    protected string $messageText = "";

    /**
     * @param $messageKey
     * @param array $replace
     * @return bool
     */
    public function rejectTrans(string $messageKey, array $replace = []): bool
    {
        return $this->reject(trans($this->messageTransPrefix . $messageKey, $replace));
    }

    /**
     * @param \Illuminate\Contracts\Translation\Translator|array|null|string $message
     *
     * @return false
     */
    public function reject(array|string|\Illuminate\Contracts\Translation\Translator|null $message): bool
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