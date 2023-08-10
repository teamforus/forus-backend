<?php

namespace App\Http\Requests\Api\Platform\Organizations;

use App\Models\Organization;
use App\Services\BIConnectionService\BIConnection;

/**
 * @property-read Organization $organization
 */
class UpdateOrganizationBIConnectionRequest extends BaseOrganizationRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return $this->organization->allow_bi_connection;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'bi_connection_auth_type' => 'nullable|in:' . implode(',', BIConnection::AUTH_TYPES),
        ];
    }
}
