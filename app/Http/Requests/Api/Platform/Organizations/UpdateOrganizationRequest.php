<?php

namespace App\Http\Requests\Api\Platform\Organizations;

use App\Helpers\Arr;
use App\Models\Organization;
use App\Models\OrganizationContact;
use App\Rules\Base\BtwRule;
use App\Rules\Base\IbanRule;
use App\Rules\Base\KvkRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Config;
use Illuminate\Validation\Rule;

/**
 * @property Organization|null $organization
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
        $kvkDebug = Config::get('forus.kvk-api.debug', false);
        $kvkGeneric = $kvk === Organization::GENERIC_KVK;
        $auth2FAPolicies = implode(',', Organization::AUTH_2FA_POLICIES);

        $kvkUniqueRule = $this->organization ? Rule::unique('organizations', 'kvk')->ignore(
            $this->organization->id
        ): Rule::unique('organizations', 'kvk');

        return [
            'name'                  => 'nullable|string|between:2,64',
            'description'           => 'nullable|string|max:4096',
            'iban'                  => ['nullable', new IbanRule()],
            'email'                 => 'nullable|email:strict',
            'email_public'          => 'nullable|boolean',
            'phone'                 => 'nullable|digits_between:4,20',
            'phone_public'          => 'nullable|boolean',
            'kvk'                   => [
                'nullable',
                'digits:8',
                $kvkDebug || $kvkGeneric ? null : $kvkUniqueRule,
                $kvkGeneric ? null : new KvkRule(),
            ],
            'btw'                   => ['nullable', new BtwRule()],
            'website'               => 'nullable|max:200|url',
            'website_public'        => 'nullable|boolean',
            'business_type_id'      => 'nullable|exists:business_types,id',
            'auth_2fa_policy'       => "nullable|in:$auth2FAPolicies",
            'auth_2fa_remember_ip'  => 'nullable|boolean',
            ...$this->contactsRules(),
        ];
    }

    /**
     * @return string[]
     */
    public function contactsRules(): array
    {
        $keys = Arr::pluck(OrganizationContact::AVAILABLE_TYPES, 'key');

        return [
            'contacts' => 'nullable|array',
            'contacts.*' => 'required|array',
            'contacts.*.key' => 'required|in:' . implode(',', $keys),
            'contacts.*.value' => 'nullable|email|string|max:100',
        ];
    }

    /**
     * @return string[]
     */
    public function attributes(): array
    {
        return [
            'contacts.*.value' => 'Contact',
        ];
    }
}
