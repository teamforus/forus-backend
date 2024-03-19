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
     * Get the validation rules that apply to the request.
     *
     * @return string[]
     *
     * @psalm-return array{bi_connection_auth_type: string}
     */
    public function rules(): array
    {
        return [
            'bi_connection_auth_type' => 'nullable|in:' . implode(',', BIConnection::AUTH_TYPES),
        ];
    }
}
