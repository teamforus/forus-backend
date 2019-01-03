<?php

namespace App\Http\Requests\Api\Platform\Organizations;

use App\Rules\Base\BtwRule;
use App\Rules\Base\IbanRule;
use App\Rules\Base\KvkRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreOrganizationRequest extends FormRequest
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
            'email_public'          => 'boolean',
            'phone'                 => 'required|digits_between:6,20',
            'phone_public'          => 'boolean',
            'kvk'                   => ['required', new KvkRule()],
            'btw'                   => [new BtwRule()],
            'website'               => 'url|max:200',
            'website_public'        => 'boolean',
            'product_categories'    => 'present|array',
            'product_categories.*'  => 'exists:product_categories,id',
        ];
    }
}
