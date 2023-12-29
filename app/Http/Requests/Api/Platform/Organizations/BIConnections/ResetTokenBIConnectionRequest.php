<?php

namespace App\Http\Requests\Api\Platform\Organizations\BIConnections;

use App\Http\Requests\BaseFormRequest;
use App\Models\Organization;

/**
 * @property-read Organization $organization
 */
class ResetTokenBIConnectionRequest extends BaseFormRequest
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
        return [];
    }
}
