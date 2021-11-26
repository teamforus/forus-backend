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
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return Gate::allows('update', [$this->bankConnection, $this->organization]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
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
