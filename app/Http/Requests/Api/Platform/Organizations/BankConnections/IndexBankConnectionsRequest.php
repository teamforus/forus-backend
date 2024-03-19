<?php

namespace App\Http\Requests\Api\Platform\Organizations\BankConnections;

use App\Http\Requests\BaseFormRequest;
use App\Models\BankConnection;
use App\Models\Organization;
use Illuminate\Support\Facades\Gate;

/**
 * @property-read Organization $organization
 */
class IndexBankConnectionsRequest extends BaseFormRequest
{


    /**
     * Get the validation rules that apply to the request.
     *
     * @return string[]
     *
     * @psalm-return array{per_page: string, state: string}
     */
    public function rules(): array
    {
        $allowedStates = array_diff(BankConnection::STATES, [
            BankConnection::STATE_REJECTED,
            BankConnection::STATE_PENDING,
            BankConnection::STATE_ERROR,
        ]);

        return [
            'per_page' => $this->perPageRule(),
            'state' => 'nullable|in:' . implode(',', $allowedStates)
        ];
    }
}
