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
     * Get the validation rules that apply to the request.
     *
     * @return ((PrevalidationDataRule|string)[]|string)[]
     *
     * @psalm-return array{fund_id: string, data: list{'required', 'array', PrevalidationDataRule}, overwrite: 'nullable|array', 'overwrite.*': 'required'}
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
            FundQuery::whereIsInternal($builder);
            FundQuery::whereIsConfiguredByForus($builder);
        })->where('state', '!=', Fund::STATE_CLOSED);
    }
}
