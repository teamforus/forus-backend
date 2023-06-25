<?php

namespace App\Http\Requests\Api\Platform\Organizations\BIConnection;

use App\Http\Requests\BaseFormRequest;
use App\Models\BIConnection;
use Illuminate\Validation\Rule;

class StoreBIConnectionRequest extends BaseFormRequest
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
            'auth_type' => [
                'required',
                Rule::in(BIConnection::$authTypes),
            ],
        ];
    }
}
