<?php

namespace App\Http\Requests\Api\Platform\Prevalidations;

use App\Exports\PrevalidationsExport;
use App\Http\Requests\BaseFormRequest;
use App\Models\Organization;
use App\Models\Permission;
use App\Models\Prevalidation;
use Illuminate\Database\Query\Builder as QBuilder;
use Illuminate\Validation\Rule;

class SearchPrevalidationsRequest extends BaseFormRequest
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
        return [
            'q' => 'nullable|string|max:200',
            'fund_id' => [
                'nullable',
                Rule::exists('funds', 'id')->where(function (QBuilder $builder) {
                    $builder->addWhereExistsQuery(Organization::queryByIdentityPermissions(
                        $this->auth_address(),
                        Permission::VALIDATE_RECORDS,
                    )->getQuery());
                }),
            ],
            'from' => 'nullable|date|date_format:Y-m-d',
            'to' => 'nullable|date|date_format:Y-m-d',
            'state' => [
                'nullable',
                Rule::in(Prevalidation::STATES),
            ],
            'per_page' => 'nullable|numeric|between:1,2500',
            'exported' => 'boolean',
            ...$this->exportableResourceRules(PrevalidationsExport::getExportFieldsRaw()),
        ];
    }
}
