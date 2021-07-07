<?php

namespace App\Rules\Misc;

use Illuminate\Contracts\Validation\Rule;

/**
 * Class ProxyRule
 * @package App\Rules\Misc
 */
class ProxyRule implements Rule
{
    protected $shouldPass;
    protected $errorMessage;

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($shouldPass = false, $errorMessage = 'The validation error message.')
    {
        $this->shouldPass = $shouldPass;
        $this->errorMessage = $errorMessage;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value): bool
    {
        return $this->shouldPass;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message(): string
    {
        return $this->errorMessage;
    }
}
