<?php

namespace App\Http\Requests\Api\Platform\Organizations;

use App\Http\Resources\OrganizationResource;
use App\Rules\DependencyRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Class IndexOrganizationRequest
 * @property string $dependency
 * @package App\Http\Requests\Api\Platform\Organizations
 */
class IndexOrganizationRequest extends FormRequest
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
        return [
            'per_page'  => 'numeric|between:1,100',
            'role' => [
                'nullable', 'string', 'exists:roles,key'
            ],
            'dependency' => ['nullable', new DependencyRule(
                OrganizationResource::DEPENDENCIES
            )]
        ];
    }
}
