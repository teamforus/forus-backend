<?php

namespace App\Rules;

use App\Http\Requests\BaseFormRequest;
use Illuminate\Contracts\Validation\Rule;

class RecordTypeKeyExistsRule implements Rule
{
    protected $allowSystemKeys;
    protected $recordRepo;

    /**
     * Create a new rule instance.
     *
     * @param BaseFormRequest $baseFormRequest
     * @param bool $allowSystemKeys
     */
    public function __construct(
        BaseFormRequest $baseFormRequest,
        bool $allowSystemKeys = false
    ) {
        $this->recordRepo = $baseFormRequest->records_repo();
        $this->allowSystemKeys = $allowSystemKeys;
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
        return in_array(array_pluck($this->recordRepo->getRecordTypes(
            $this->allowSystemKeys
        ), 'key'), $value);
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message(): string
    {
        return trans('validation.exists');
    }
}
