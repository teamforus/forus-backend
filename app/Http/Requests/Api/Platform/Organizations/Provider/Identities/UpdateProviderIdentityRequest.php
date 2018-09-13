<?php

namespace App\Http\Requests\Api\Platform\Organizations\Provider\Identities;

use App\Rules\IdentityRecordsExistsRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProviderIdentityRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'email' => ['required', new IdentityRecordsExistsRule('primary_email')],
        ];
    }
}
