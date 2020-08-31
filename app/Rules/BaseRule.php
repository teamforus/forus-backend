<?php


namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

/**
 * Class BaseRule
 * @package App\Rules
 */
abstract class BaseRule implements Rule
{
    protected $messageTransPrefix = "";
    protected $message = "";

    /**
     * @param $messageKey
     * @param array $replace
     * @return bool
     */
    public function rejectTrans($messageKey, $replace = []): bool {
        return $this->rejectWithMessage(
            trans($this->messageTransPrefix . $messageKey, $replace)
        );
    }

    /**
     * @param $message
     * @return bool
     */
    public function rejectWithMessage($message): bool {
        $this->message = $message;

        return false;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message(): string {
        return $this->message;
    }
}