<?php

namespace App\Http\Requests\Api\Platform\Prevalidations;

use App\Http\Requests\BaseFormRequest;
use App\Models\Fund;
use App\Models\Prevalidation;
use App\Rules\PrevalidationDataRule;
use App\Scopes\Builders\FundQuery;
use App\Scopes\Builders\OrganizationQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;

class UploadPrevalidationsRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return Gate::allows('store', Prevalidation::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array {
        $fundsAvailable = $this->getAvailableFunds()->pluck('id');

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

    /**
     * @return Builder
     */
    private function getAvailableFunds(): Builder
    {
        return Fund::whereHas('organization', function(Builder $builder) {
            OrganizationQuery::whereHasPermissions($builder, $this->auth_address(), 'validate_records');
        })->where(function(Builder $builder) {
            FundQuery::whereIsConfiguredByForus($builder);
        })->where(function(Builder $builder) {
            FundQuery::whereIsInternal($builder);
        })->where('state', '!=', Fund::STATE_CLOSED);
    }
}
