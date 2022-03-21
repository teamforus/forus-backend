<?php

namespace App\Http\Requests\Api\Platform\Organizations;

use App\Models\Organization;
use App\Rules\Base\BtwRule;
use App\Rules\Base\IbanRule;
use App\Rules\Base\KvkRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Class UpdateOrganizationRequest
 * @property Organization|null $organization
 * @package App\Http\Requests\Api\Platform\Organizations
 */
class UpdateOrganizationRequest extends FormRequest
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
        $kvk = $this->input('kvk');
        $kvkDebug = env("KVK_API_DEBUG", false);
        $kvkGeneric = $kvk === Organization::GENERIC_KVK;

        $kvkUniqueRule = $this->organization ? Rule::unique('organizations', 'kvk')->ignore(
            $this->organization->id
        ): Rule::unique('organizations', 'kvk');

        return [
            'name'                  => 'required|between:2,64',
            'description'           => 'nullable|string|max:4096',
            'iban'                  => ['required', new IbanRule()],
            'email'                 => 'required|email:strict',
            'email_public'          => 'boolean',
            'phone'                 => 'required|digits_between:4,20',
            'phone_public'          => 'boolean',
            'kvk'                   => [
                'required',
                'digits:8',
                $kvkDebug || $kvkGeneric ? null : $kvkUniqueRule,
                $kvkGeneric ? null : new KvkRule(),
            ],
            'btw'                   => [new BtwRule()],
            'website'               => 'nullable|max:200|url',
            'website_public'        => 'boolean',
            'business_type_id'      => 'required|exists:business_types,id',
        ];
    }
}
