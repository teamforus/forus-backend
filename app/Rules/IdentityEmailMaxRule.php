<?php

namespace App\Rules;

use App\Models\IdentityEmail;
use Illuminate\Contracts\Validation\Rule;

class IdentityEmailMaxRule implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct(protected ?string $identity_address) {}

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     * @throws \Exception
     */
    public function passes($attribute, $value): bool
    {
        $count = IdentityEmail::where('identity_address', $this->identity_address)->count();

        return $count < config('forus.mail.max_identity_emails');
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message(): string
    {
        return trans('validation.max_emails_reached', [
            'max' => config('forus.mail.max_identity_emails')
        ]);
    }
}
