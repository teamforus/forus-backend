<?php

namespace App\Http\Requests\Api\Platform\Organizations\BankConnections;

use App\Http\Requests\BaseFormRequest;
use App\Models\BankConnection;
use App\Models\Organization;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * @property-read Organization $organization
 * @property-read BankConnection $bankConnection
 */
class UpdateBankConnectionsRequest extends BaseFormRequest
{


    /**
     * Get the validation rules that apply to the request.
     *
     * @return (\Illuminate\Validation\Rules\In|string)[][]
     *
     * @psalm-return array{state: list{'nullable', \Illuminate\Validation\Rules\In}, bank_connection_account_id: list{'nullable', \Illuminate\Validation\Rules\In}}
     */
    public function rules(): array
    {
        $bankConnectionAccounts = $this->bankConnection->bank_connection_accounts()->pluck('id');

        return [
            'state' => [
                'nullable',
                Rule::in(BankConnection::STATE_DISABLED),
            ],
            'bank_connection_account_id' => [
                'nullable',
                Rule::in($bankConnectionAccounts->values()),
            ],
        ];
    }
}
