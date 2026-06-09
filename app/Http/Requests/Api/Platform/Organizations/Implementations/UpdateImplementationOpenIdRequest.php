<?php

namespace App\Http\Requests\Api\Platform\Organizations\Implementations;

use App\Services\OpenIdService\Models\OpenIdFlow;
use App\Services\OpenIdService\OpenIdService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateImplementationOpenIdRequest extends FormRequest
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
        $flowKeys = OpenIdFlow::configuredForProvider(OpenIdService::PROVIDER_VERID)->pluck('key')->all();

        return [
            'openid_enabled' => 'required|boolean',
            'openid_flow_keys' => ['present', 'array'],
            'openid_flow_keys.*' => ['string', Rule::in($flowKeys)],
        ];
    }
}
