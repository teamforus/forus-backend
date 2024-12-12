<?php

namespace App\Http\Requests\Api\Platform\Organizations\Sponsor\Identities\BankAccounts;

use App\Models\ProfileBankAccount;
use Illuminate\Support\Facades\Gate;

/**
 * @property-read ProfileBankAccount $profileBankAccount
 */
class UpdateProfileBankAccountRequest extends BaseProfileBankAccountRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Gate::allows('updateSponsorIdentitiesBankAccounts', [
            $this->organization,
            $this->identity,
            $this->profileBankAccount,
        ]);
    }
}
