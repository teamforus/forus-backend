<?php

namespace App\Http\Requests\Api\Platform\Organizations;

use App\Helpers\Arr;
use App\Http\Requests\BaseFormRequest;
use App\Models\Organization;
use App\Models\OrganizationContact;
use App\Rules\Base\BtwRule;
use App\Rules\Base\IbanRule;
use App\Rules\Base\KvkRule;
use Illuminate\Support\Facades\Config;
use Illuminate\Validation\Rule;

/**
 * @property Organization|null $organization
 */
class UpdateOrganizationRequest extends BaseFormRequest
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

        $kvkUniqueRule = $this->organization ? Rule::unique('organizations', 'kvk')->ignore(
            $this->organization->id
        ): Rule::unique('organizations', 'kvk');

        return [
            'name'                  => 'nullable|string|between:2,64',
            'description'           => 'nullable|string|max:4096',
            'iban'                  => ['nullable', new IbanRule()],
            'email'                 => [
                'nullable',
                ...$this->emailRules(),
            ],
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
            ...$this->auth2FARules(),
            ...$this->contactsRules(),
        ];
    }

    /**
     * @return string[]
     */
    public function auth2FARules(): array
    {
        $auth2FAPolicies = implode(',', Organization::AUTH_2FA_POLICIES);
        $auth2FAFundsPolicies = implode(',', Organization::AUTH_2FA_FUNDS_POLICIES);

        return [
            'auth_2fa_policy' => "nullable|in:$auth2FAPolicies",
            'auth_2fa_remember_ip' => 'nullable|boolean',
            'auth_2fa_funds_policy' => "nullable|in:$auth2FAFundsPolicies",
            'auth_2fa_funds_remember_ip' => 'nullable|boolean',
            'auth_2fa_funds_restrict_emails' => 'nullable|boolean',
            'auth_2fa_funds_restrict_auth_sessions' => 'nullable|boolean',
            'auth_2fa_funds_restrict_reimbursements' => 'nullable|boolean',
            'auth_2fa_restrict_bi_connections' => 'nullable|boolean',
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
            'contacts.*.value' => [
                'nullable',
                ...$this->emailRules(),
            ],
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
