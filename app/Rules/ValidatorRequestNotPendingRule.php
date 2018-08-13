<?php

namespace App\Rules;

use App\Models\ValidatorRequest;
use Illuminate\Contracts\Validation\Rule;

class ValidatorRequestNotPendingRule implements Rule
{
    protected $validatorId;

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct(
        $validatorId
    ) {
        $this->validatorId = $validatorId;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        return ValidatorRequest::getModel()->where([
            'validator_id' => $this->validatorId,
            'record_id' => $value,
            'state' => 'pending'
        ])->count() == 0;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return trans('validation.validator_request_is_pending');
    }
}
