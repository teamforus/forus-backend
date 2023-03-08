<?php

namespace App\Http\Requests\Api\Platform\Organizations;

use App\Http\Requests\BaseFormRequest;
use App\Traits\ValidatesFaq;

class StoreFaqValidateRequest extends BaseFormRequest
{
    use ValidatesFaq;

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return $this->faqRules();
    }

    /**
     * @return array
     */
    public function attributes(): array
    {
        return $this->getFaqAttributes();
    }
}