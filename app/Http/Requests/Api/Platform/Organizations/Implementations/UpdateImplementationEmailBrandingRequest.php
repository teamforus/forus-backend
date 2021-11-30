<?php

namespace App\Http\Requests\Api\Platform\Organizations\Implementations;

use App\Rules\MediaUidRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateImplementationEmailBrandingRequest extends FormRequest
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
            'email_color'       => 'nullable|string|size:7|starts_with:#',
            'email_signature'   => 'nullable|string|max:4096',
            'email_logo_uid'    => ['nullable', new MediaUidRule('email_logo')]
        ];
    }
}
