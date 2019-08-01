<?php

namespace App\Http\Requests\Api\Platform;

use App\Models\Organization;
use App\Rules\PrevalidationDataRule;
use Illuminate\Foundation\Http\FormRequest;

class UploadPrevalidationsRequest extends FormRequest
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
        $fundsAvailable = Organization::queryByIdentityPermissions(
            auth()->id(),
            'validate_records'
        )->get()->pluck('funds')->flatten()->pluck('id');

        return [
            'fund_id' => 'required|in:' . $fundsAvailable->implode(','),
            'data' => ['required', 'array', new PrevalidationDataRule(
                request()->input('fund_id')
            )]
        ];
    }
}
