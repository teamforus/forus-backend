<?php

namespace App\Http\Requests\Api\Platform\Prevalidations;

use App\Models\Organization;
use Illuminate\Foundation\Http\FormRequest;

class SearchPrevalidationsRequest extends FormRequest
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
            'q' => '',
            'fund_id' => 'in:' . $fundsAvailable->implode(','),
            'from' => 'date_format:Y-m-d',
            'to' => 'date_format:Y-m-d',
            'exported' => 'boolean'
        ];
    }
}
