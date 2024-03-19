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
     * @return string[]
     *
     * @psalm-return array{roles: 'present|array', 'roles.*': 'exists:roles,id', office_id: string}
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
