<?php

namespace App\Http\Requests\Api\Platform\Organizations\Employees;

use App\Http\Requests\BaseFormRequest;
use App\Models\Organization;

/**
 * @property Organization $organization
 */
abstract class BaseEmployeeRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return $this->isAuthenticated();
    }

    /**
     * @return array
     */
    public function updateRules(): array
    {
        $offices = $this->organization->offices()->pluck('id');

        return [
            'roles' => 'present|array',
            'roles.*' => 'exists:roles,id',
            'office_id' => 'nullable|exists:offices,id|in:' . $offices->join(','),
        ];
    }
}
