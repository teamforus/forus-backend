<?php

namespace App\Rules\Base;

use Illuminate\Contracts\Validation\Rule;

class EthAddressRule implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param string  $attribute
     * @param mixed  $value
     *
     * @return false|int
     *
     * @psalm-return 0|1|false
     */
    public function passes($attribute, $value): int|false
    {
        return preg_match('/^(0x)?[0-9a-f]{40}$/i', $value);
    }

    /**
     * Get the validation error message.
     *
     * @return \Illuminate\Contracts\Translation\Translator|array|null|string
     */
    public function message(): array|string|\Illuminate\Contracts\Translation\Translator|null
    {
        return trans('validation.eth_address');
    }
}
