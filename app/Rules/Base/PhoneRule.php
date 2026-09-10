<?php

namespace App\Rules\Base;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\Validator;

class PhoneRule implements Rule
{
    private array $messages = [];

    /**
     * @param string $attribute
     * @param mixed $value
     * @return bool
     */
    public function passes($attribute, $value): bool
    {
        $validator = Validator::make([
            'phone' => $value,
        ], [
            'phone' => ['string', 'min:4', 'max:20', 'regex:/^[0-9+()\- ]{4,20}$/'],
        ], [], [
            'phone' => ':attribute',
        ]);

        $this->messages = $validator->errors()->all();

        return $this->messages === [];
    }

    /**
     * @return array
     */
    public function message(): array
    {
        return $this->messages;
    }
}
