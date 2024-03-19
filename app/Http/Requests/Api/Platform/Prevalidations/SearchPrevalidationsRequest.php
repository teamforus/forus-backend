<?php

namespace App\Http\Requests\Api\Platform\Prevalidations;

use App\Models\Organization;
use App\Models\Prevalidation;
use Illuminate\Database\Query\Builder as QBuilder;
use App\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rule;

class SearchPrevalidationsRequest extends BaseFormRequest
{


    /**
     * Get the validation rules that apply to the request.
     *
     * @return ((\Illuminate\Validation\Rules\Exists|\Illuminate\Validation\Rules\In|string)[]|string)[]
     *
     * @psalm-return array{q: 'nullable|string|max:200', fund_id: list{'nullable', \Illuminate\Validation\Rules\Exists}, from: 'nullable|date|date_format:Y-m-d', to: 'nullable|date|date_format:Y-m-d', state: list{'nullable', \Illuminate\Validation\Rules\In}, per_page: 'nullable|numeric|between:1,2500', exported: 'boolean', export_format: 'nullable|string|in:csv,xls'}
     */
    public function rules(): array
    {
        return [
            'q' => 'nullable|string|max:200',
            'fund_id' => [
                'nullable',
                Rule::exists('funds', 'id')->where(function(QBuilder $builder) {
                    $builder->addWhereExistsQuery(Organization::queryByIdentityPermissions(
                        $this->auth_address(), 'validate_records'
                    )->getQuery());
                })
            ],
            'from' => 'nullable|date|date_format:Y-m-d',
            'to' => 'nullable|date|date_format:Y-m-d',
            'state' => [
                'nullable',
                Rule::in(Prevalidation::STATES)
            ],
            'per_page' => 'nullable|numeric|between:1,2500',
            'exported' => 'boolean',
            'export_format' => 'nullable|string|in:csv,xls'
        ];
    }
}
