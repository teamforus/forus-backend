<?php

namespace App\Http\Requests\Api\Identity;

use App\Models\Organization;
use Illuminate\Foundation\Http\FormRequest;

class ApproveRecordValidationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $organizationsAvailable = Organization::queryByIdentityPermissions(
            auth()->id(), 'validate_records'
        )->pluck('id');

        return [
            'organization_id' => [
                'nullable',
                'in:' . $organizationsAvailable->implode(',')
            ],
        ];
    }
}
