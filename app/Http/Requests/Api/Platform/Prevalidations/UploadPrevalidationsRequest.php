<?php

namespace App\Http\Requests\Api\Platform\Prevalidations;

use App\Http\Requests\BaseFormRequest;
use App\Models\Fund;
use App\Models\Organization;
use App\Rules\PrevalidationDataRule;

class UploadPrevalidationsRequest extends BaseFormRequest
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
    public function rules(): array {
        $fundsAvailable = Organization::queryByIdentityPermissions($this->auth_address(), [
            'validate_records'
        ])->get()->pluck('funds')->flatten()->filter(static function (Fund $fund) {
            return $fund->state !== Fund::STATE_CLOSED;
        })->pluck('id');

        return [
            'fund_id' => 'required|in:' . $fundsAvailable->implode(','),
            'data' => [
                'required',
                'array',
                new PrevalidationDataRule($this->input('fund_id'))
            ],
            'overwrite' => 'nullable|array',
            'overwrite.*' => 'required',
        ];
    }
}
