<?php

namespace App\Http\Requests\Api\Platform\Feedback;

use App\Http\Requests\BaseFormRequest;

class StoreFeedbackRequest extends BaseFormRequest
{


    /**
     * Get the validation rules that apply to the request.
     *
     * @return ((mixed|string)[]|string)[]
     *
     * @psalm-return array{title: 'required|string|min:2|max:200', content: 'required|string|min:2|max:4000', urgency: 'nullable|in:low,medium,high', customer_email: array{0: 'nullable'|mixed,...}}
     */
    public function rules(): array
    {
        return [
            'title' => 'required|string|min:2|max:200',
            'content' => 'required|string|min:2|max:4000',
            'urgency' => 'nullable|in:low,medium,high',
            'customer_email' => [
                'nullable',
                ...$this->emailRules(),
            ],
        ];
    }
}
