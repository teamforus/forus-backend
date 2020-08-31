<?php

namespace App\Http\Requests\Api\Platform\Prevalidations;

use App\Models\Organization;
use App\Models\Prevalidation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SearchPrevalidationsRequest extends FormRequest
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
        $fundsAvailable = Organization::queryByIdentityPermissions(
            auth()->id(),
            'validate_records'
        )->get()->pluck('funds')->flatten()->pluck('id');

        return [
            'q' => '',
            'fund_id' => 'in:' . $fundsAvailable->implode(','),
            'from' => 'date_format:Y-m-d',
            'to' => 'date_format:Y-m-d',
            'state' => [
                'nullable', Rule::in(Prevalidation::STATES)
            ],
            'per_page' => 'nullable|numeric|between:1,2500',
            'exported' => 'boolean'
        ];
    }
}
