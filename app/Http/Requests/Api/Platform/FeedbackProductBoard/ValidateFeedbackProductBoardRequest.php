<?php

namespace App\Http\Requests\Api\Platform\FeedbackProductBoard;

use Illuminate\Foundation\Http\FormRequest;

class ValidateFeedbackProductBoardRequest extends FormRequest
{
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
        return [
            'title'   => 'required',
            'tags'    => 'nullable|array',
            'tags.*'  => 'in:low,medium,high',
            'content' => 'required',
            'use_customer_email' => 'required|boolean',
            'customer_email' => 'nullable|email'
        ];
    }

    /**
     * @return array
     */
    public function messages(): array
    {
        return [
            'tags.*.in' => 'Geselecteerde urgentie is ongeldig.',
        ];
    }
}
