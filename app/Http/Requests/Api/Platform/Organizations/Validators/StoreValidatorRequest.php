<?php

namespace App\Http\Requests\Api\Platform\Organizations\Funds\Validators;

use App\Rules\Base\EthAddressRule;
use App\Rules\IdentityRecordsExistsRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreValidatorRequest extends FormRequest
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
            // 'identity_address'  => ['required', new EthAddressRule()]
            'email' => ['required', new IdentityRecordsExistsRule('primary_email')],
        ];
    }
}
