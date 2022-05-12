<?php

namespace App\Http\Requests\Api\Platform\Organizations\Funds;

use App\Http\Requests\BaseFormRequest;
use App\Models\Fund;
use App\Models\FundConfig;
use App\Models\Organization;

/**
 * Class UpdateFundRequest
 * @property null|Fund $fund
 * @property null|Organization $organization
 * @package App\Http\Requests\Api\Platform\Organizations\Funds
 */
class UpdateFundBackofficeRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return $this->isAuthenticated() &&
            $this->fund->organization_id === $this->organization->id &&
            $this->organization->identityCan($this->auth_address(), 'manage_funds');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        $ineligiblePolicies = implode(',', FundConfig::BACKOFFICE_INELIGIBLE_POLICIES);

        return [
            'backoffice_enabled'                    => 'nullable|boolean',
            'backoffice_url'                        => 'nullable|string|min:0|max:191|url|starts_with:https://',
            'backoffice_key'                        => 'nullable|string|min:0|max:191',
            'backoffice_certificate'                => 'nullable|string|min:0|max:8000',
            'backoffice_fallback'                   => 'nullable|boolean',
            'backoffice_ineligible_policy'          => 'required|in:' . $ineligiblePolicies,
            'backoffice_ineligible_redirect_url'    => $this->notEligibleRedirectRules(),
        ];
    }

    /**
     * @return string[]
     */
    protected function notEligibleRedirectRules(): array
    {
        return [
            'required_if:backoffice_ineligible_policy,' . FundConfig::BACKOFFICE_INELIGIBLE_POLICY_REDIRECT,
            'string',
            'min:0',
            'max:2000',
            'url',
            'starts_with:https://',
        ];
    }
}
