<?php

namespace App\Http\Requests\Api\Identity;

use App\Http\Requests\BaseFormRequest;
use App\Models\Organization;

class ApproveRecordValidationRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
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
