<?php

namespace App\Http\Requests\Api\Platform\Organizations\PrevalidationRequests;

use App\Http\Requests\BaseFormRequest;
use App\Models\Organization;
use App\Models\PrevalidationRequest;
use App\Services\IConnectApiService\IConnectPrefill;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * @property-read Organization $organization
 */
class ResubmitFailedPrevalidationRequestsRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return Gate::allows('resubmitFailed', [PrevalidationRequest::class, $this->organization]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'reason' => [
                'nullable',
                Rule::in([
                    IConnectPrefill::PREFILL_ERROR_NOT_FOUND,
                    IConnectPrefill::PREFILL_ERROR_CONNECTION_ERROR,
                    IConnectPrefill::PREFILL_ERROR_TAKEN_BY_PARTNER,
                    IConnectPrefill::PREFILL_ERROR_NOT_FILLED_REQUIRED_CRITERIA,
                    PrevalidationRequest::FAILED_REASON_INVALID_RECORDS,
                    PrevalidationRequest::FAILED_REASON_EMPTY_PREVALIDATIONS,
                ]),
            ],
        ];
    }
}
