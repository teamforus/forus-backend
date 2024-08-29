<?php

namespace App\Http\Requests\Api\Identity;

use App\Http\Requests\BaseFormRequest;
use App\Models\Organization;
use App\Models\Permission;
use Illuminate\Validation\Rule;

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
        $organizationsAvailable = Organization::queryByIdentityPermissions($this->auth_address(), [
            Permission::VALIDATE_RECORDS,
        ]);

        return [
            'organization_id' => [
                'nullable',
                Rule::in($organizationsAvailable->pluck('id')->toArray()),
            ],
        ];
    }
}
