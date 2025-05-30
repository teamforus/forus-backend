<?php

namespace App\Http\Requests\Api\Platform\Demo;

use App\Models\DemoTransaction;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTransactionRequest extends FormRequest
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
            'state' => [
                'required',
                Rule::in(DemoTransaction::STATES),
            ],
        ];
    }
}
