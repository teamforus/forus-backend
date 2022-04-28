<?php

namespace App\Http\Requests\BNG;

use App\Models\BankConnection;

/**
 * @property-read BankConnection $bngBankConnectionToken
 */
class RedirectBNGBankConnectionRequest extends BaseBNGRedirectRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        if ($this->bngBankConnectionToken->auth_params['state'] !== $this->get('state')) {
            abort(403, 'Invalid request.');
        }

        if (!$this->bngBankConnectionToken->isPending()) {
            abort(403, 'Bulk is not pending.');
        }

        if (!$this->bngBankConnectionToken->bank->isBNG()) {
            abort(403, 'Invalid connection type.');
        }

        return true;
    }
}
