<?php

namespace App\Http\Requests\Api\Platform\Organizations;

use App\Http\Requests\BaseFormRequest;
use App\Traits\ValidatesFaq;

class StoreFaqValidateRequest extends BaseFormRequest
{
    use ValidatesFaq;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return string[]
     *
     * @psalm-return array<string>
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