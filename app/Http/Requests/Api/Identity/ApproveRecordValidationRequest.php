<?php

namespace App\Http\Requests\Api\Identity;

use App\Http\Requests\BaseFormRequest;
use App\Models\Organization;

class ApproveRecordValidationRequest extends BaseFormRequest
{


    /**
     * Get the validation rules that apply to the request.
     *
     * @return string[][]
     *
     * @psalm-return array{organization_id: list{'nullable', string}}
     */
    public function rules(): array
    {
        $organizationsAvailable = Organization::queryByIdentityPermissions(
            $this->auth_address(), 'validate_records'
        )->pluck('id');

        return [
            'organization_id' => [
                'nullable',
                'in:' . $organizationsAvailable->implode(',')
            ],
        ];
    }
}
