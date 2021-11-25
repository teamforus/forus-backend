<?php

namespace App\Http\Requests\Api\Platform\Organizations\BankConnections;

use App\Http\Requests\BaseFormRequest;
use App\Models\BankConnection;
use App\Models\Organization;
use Illuminate\Support\Facades\Gate;

/**
 * @property-read Organization $organization
 */
class StoreBankConnectionsRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return Gate::allows('store', [BankConnection::class, $this->organization]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'bank_id' => 'required|exists:banks,id',
        ];
    }
}
