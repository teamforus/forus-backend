<?php

namespace App\Http\Requests\Api\Platform\Organizations\BankConnections;

use App\Models\BankConnection;
use App\Models\Organization;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

/**
 * @property-read Organization $organization
 */
class UpdateBankConnectionsRequest extends FormRequest
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
            'state' => 'nullable|in:' . implode(',', [BankConnection::STATE_DISABLED]),
        ];
    }
}
