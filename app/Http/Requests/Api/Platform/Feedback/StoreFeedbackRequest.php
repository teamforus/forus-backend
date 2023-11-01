<?php

namespace App\Http\Requests\Api\Platform\Feedback;

use App\Http\Requests\BaseFormRequest;

class StoreFeedbackRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return $this->isAuthenticated();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'title' => 'required|string|min:2|max:200',
            'content' => 'required|string|min:2|max:4000',
            'urgency' => 'nullable|in:low,medium,high',
            'customer_email' => 'nullable|email',
        ];
    }
}
