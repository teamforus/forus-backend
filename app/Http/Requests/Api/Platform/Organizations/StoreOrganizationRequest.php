<?php

namespace App\Http\Requests\Api\Platform\Organizations;

use App\Http\Requests\BaseFormRequest;
use App\Models\Organization;
use App\Rules\Base\BtwRule;
use App\Rules\Base\IbanRule;
use App\Rules\Base\KvkRule;

class StoreOrganizationRequest extends BaseFormRequest
{


    /**
     * Get the validation rules that apply to the request.
     *
     * @return ((BtwRule|IbanRule|KvkRule|mixed|null|string)[]|string)[]
     *
     * @psalm-return array{name: 'required|between:2,64', description: 'nullable|string|max:4096', iban: list{'required', IbanRule}, email: array{0: 'required'|mixed,...}, email_public: 'boolean', phone: 'required|digits_between:4,20', phone_public: 'boolean', kvk: list{'required', 'digits:8', 'unique:organizations,kvk'|null, KvkRule|null}, btw: list{'nullable', 'string', BtwRule}, website: 'nullable|max:200|url', website_public: 'boolean', business_type_id: 'required|exists:business_types,id'}
     */
    public function rules(): array
    {
        $kvk = $this->input('kvk');
        $kvkDebug = env("KVK_API_DEBUG", false);
        $kvkGeneric = $kvk === Organization::GENERIC_KVK;

        return [
            'name'                  => 'required|between:2,64',
            'description'           => 'nullable|string|max:4096',
            'iban'                  => ['required', new IbanRule()],
            'email'                 => [
                'required',
                ...$this->emailRules(),
            ],
            'email_public'          => 'boolean',
            'phone'                 => 'required|digits_between:4,20',
            'phone_public'          => 'boolean',
            'kvk'                   => [
                'required',
                'digits:8',
                $kvkDebug || $kvkGeneric ? null : 'unique:organizations,kvk',
                $kvkGeneric ? null : new KvkRule(),
            ],
            'btw'                   => ['nullable', 'string', new BtwRule()],
            'website'               => 'nullable|max:200|url',
            'website_public'        => 'boolean',
            'business_type_id'      => 'required|exists:business_types,id',
        ];
    }
}
