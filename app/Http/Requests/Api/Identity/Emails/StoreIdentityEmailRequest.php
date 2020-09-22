<?php

namespace App\Http\Requests\Api\Identity\Emails;

use App\Rules\IdentityEmailUniqueRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreIdentityEmailRequest extends FormRequest
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
            'email' => [
                'required', new IdentityEmailUniqueRule()
            ]
        ];
    }
}
