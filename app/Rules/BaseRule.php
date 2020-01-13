<?php


namespace App\Rules;


use Illuminate\Contracts\Validation\Rule;

abstract class BaseRule implements Rule
{
    protected $messageTransPrefix = "";
    protected $message = "";

    public function rejectTrans($messageKey) {
        return $this->rejectWithMessage(
            trans($this->messageTransPrefix . $messageKey)
        );
    }

    public function rejectWithMessage($message) {
        $this->message = $message;

        return false;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return $this->message;
    }
}