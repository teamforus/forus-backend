<?php

namespace App\Http\Requests\Api\Platform\Organizations\Vouchers;

use Illuminate\Foundation\Http\FormRequest;

class AssignVoucherRequest extends FormRequest
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
            'email' => 'required_without:bsn|email:strict,dns',
            'bsn' => 'required_without:email|string|between:8,9',
        ];
    }
}
