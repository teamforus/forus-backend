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
     * Get the validation rules that apply to the request.
     *
     * @return ((BtwRule|IbanRule|KvkRule|\Illuminate\Validation\Rules\Unique|mixed|null|string)[]|string)[]
     *
     * @psalm-return array{name: string, description: string, iban: list{'nullable', IbanRule}|string, email: array{0: 'nullable'|mixed,...}|string, email_public: string, phone: string, phone_public: string, kvk: list{'nullable', 'digits:8', \Illuminate\Validation\Rules\Unique|null, KvkRule|null}|string, btw: list{'nullable', BtwRule}|string, website: string, website_public: string, business_type_id: string,...}
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
     *
     * @psalm-return array{auth_2fa_policy: string, auth_2fa_remember_ip: 'nullable|boolean', auth_2fa_funds_policy: string, auth_2fa_funds_remember_ip: 'nullable|boolean', auth_2fa_funds_restrict_emails: 'nullable|boolean', auth_2fa_funds_restrict_auth_sessions: 'nullable|boolean', auth_2fa_funds_restrict_reimbursements: 'nullable|boolean'}
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
        ];
    }

    /**
     * @return ((mixed|string)[]|string)[]
     *
     * @psalm-return array{contacts: 'nullable|array', 'contacts.*': 'required|array', 'contacts.*.key': string, 'contacts.*.value': array{0: 'nullable'|mixed,...}}
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
     *
     * @psalm-return array{'contacts.*.value': 'Contact'}
     */
    public function attributes(): array
    {
        return [
            'contacts.*.value' => 'Contact',
        ];
    }
}
