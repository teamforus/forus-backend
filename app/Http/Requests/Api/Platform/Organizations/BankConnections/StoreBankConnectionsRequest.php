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
     * Get the validation rules that apply to the request.
     *
     * @return string[]
     *
     * @psalm-return array{bank_id: 'required|exists:banks,id'}
     */
    public function rules(): array
    {
        return array_merge([
            'bank_id' => 'required|exists:banks,id',
        ]);
    }
}
