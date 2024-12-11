<?php

namespace App\Http\Requests\Api\Platform\Organizations\Sponsor\Identities\BankAccounts;

use Illuminate\Support\Facades\Gate;

class StoreProfileBankAccountRequest extends BaseProfileBankAccountRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Gate::allows('updateSponsorIdentities', [$this->organization, $this->identity]);
    }
}
