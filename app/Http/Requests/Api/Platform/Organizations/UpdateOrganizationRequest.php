<?php

namespace App\Http\Requests\Api\Platform\Organizations;

use App\Rules\Base\BtwRule;
use App\Rules\Base\IbanRule;
use App\Rules\Base\KvkRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateOrganizationRequest extends FormRequest
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
            'name'                  => 'required|between:2,200',
            'iban'                  => ['required', new IbanRule()],
            'email'                 => 'required|email',
            'phone'                 => 'required|digits_between:6,20',
            'kvk'                   => ['required', new KvkRule()],
            'btw'                   => [new BtwRule()],
            'product_categories'    => 'present|array',
            'product_categories.*'  => 'exists:product_categories,id',
        ];
    }
}
