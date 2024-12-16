<?php

namespace App\Http\Requests\Api\Platform\Organizations\Sponsor\Identities\BankAccounts;

use App\Http\Requests\BaseFormRequest;
use App\Models\Identity;
use App\Models\Organization;
use App\Rules\Base\IbanNameRule;
use App\Rules\Base\IbanRule;

/**
 * @property-read Organization $organization
 * @property-read Identity $identity
 */
class BaseProfileBankAccountRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            'name' => ['required', new IbanNameRule()],
            'iban' => ['required', new IbanRule()],
        ];
    }
}
