<?php

namespace App\Http\Requests\BNG;

use App\Models\VoucherTransactionBulk;

/**
 * @property-read VoucherTransactionBulk $bngVoucherTransactionBulkToken
 */
class RedirectBNGVoucherTransactionBulkRequest extends BaseBNGRedirectRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        if ($this->bngVoucherTransactionBulkToken->auth_params['state'] !== $this->get('state')) {
            abort(403, 'Invalid request.');
        }

        if (!$this->bngVoucherTransactionBulkToken->isPending()) {
            abort(403, 'Connection is not pending.');
        }

        if (!$this->bngVoucherTransactionBulkToken->bank_connection->bank->isBNG()) {
            abort(403, 'Invalid connection type.');
        }

        return true;
    }
}
